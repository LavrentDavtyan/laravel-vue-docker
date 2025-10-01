<template>
    <div class="category-page">
        <div class="header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <router-link to="/incomes" class="btn btn-sm btn-secondary">← Back</router-link>
                <h2 class="m-0">Category: <span class="text-primary">{{ slug }}</span></h2>
            </div>
            <small class="text-muted">Range: {{ rangeLabel }}</small>
        </div>

        <div class="grid">
            <!-- Left: Trend card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Trend (last 90 days)</h3>
                </div>
                <div class="card-body">
                    <canvas ref="trendChart" class="chart"></canvas>
                    <div v-if="!trend.length" class="empty mt-2">No data for this range.</div>
                </div>
            </div>

            <!-- Right: KPI + top descriptions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body">
                    <div class="kpis">
                        <div class="kpi">
                            <div class="kpi-label">Total Income</div>
                            <div class="kpi-value">{{ currency(totalAmount) }}</div>
                        </div>
                        <div class="kpi">
                            <div class="kpi-label">Avg / Day</div>
                            <div class="kpi-value">{{ currency(avgPerDay) }}</div>
                        </div>
                        <div class="kpi">
                            <div class="kpi-label">Transactions</div>
                            <div class="kpi-value">{{ itemsTotal }}</div>
                        </div>
                    </div>

                    <h4 class="mt-3 mb-2">Top Descriptions</h4>
                    <ul class="top-list">
                        <li v-for="(t, i) in top" :key="i">
                            <span class="dot"></span>
                            <span class="desc">{{ t.description || '—' }}</span>
                            <span class="amount">{{ currency(Number(t.total || 0)) }}</span>
                        </li>
                        <li v-if="!top.length" class="empty">No top descriptions.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Items table -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Items</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in items.data" :key="item.id">
                        <td>{{ (item.date || '').slice(0,10) }}</td>
                        <td class="text-end">{{ currency(Number(item.amount || 0)) }}</td>
                        <td>{{ item.description }}</td>
                    </tr>
                    <tr v-if="!items.data?.length">
                        <td colspan="3" class="empty">No items in this range.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '../http'
import Chart from 'chart.js/auto'

const route = useRoute()
const slug  = route.params.slug

// data
const trend = ref([])
const top   = ref([])
const items = ref({ data: [] })

const trendChart = ref(null)
let trendInstance = null

// helpers
const rangeLabel = computed(() => {
    const q = route.query
    if (q.date_from && q.date_to) return `${q.date_from} → ${q.date_to}`
    return 'last 90 days'
})
const currency = (n) => {
    const value = isFinite(n) ? Number(n) : 0
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(value)
    } catch {
        return new Intl.NumberFormat().format(value)
    }
}

const totalAmount = computed(
    () => items.value.data?.reduce((s, it) => s + Number(it.amount || 0), 0) || 0
)
const itemsTotal  = computed(() => items.value.data?.length || 0)
const avgPerDay   = computed(() => {
    const q    = route.query
    const from = q.date_from ? new Date(q.date_from) : null
    const to   = q.date_to   ? new Date(q.date_to)   : null
    if (!from || !to) return 0
    const days = Math.max(1, Math.round((to - from) / 86400000) + 1)
    return totalAmount.value / days
})

onMounted(async () => {
    const res = await axios.get(`/incomes/category/${slug}`, { params: route.query })
    trend.value = res.data.trend || []
    top.value   = res.data.top   || []
    items.value = res.data.items || { data: [] }

    renderTrend()
})

function renderTrend () {
    if (trendInstance) {
        trendInstance.destroy()
        trendInstance = null
    }
    const labels = trend.value.map(t => t.date)
    const data   = trend.value.map(t => Number(t.total || 0))
    if (!labels.length) return

    trendInstance = new Chart(trendChart.value, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Total',
                data,
                borderColor: '#36A2EB',
                fill: false,
                tension: 0.3,
                pointBackgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Date' } },
                y: { title: { display: true, text: 'Amount' }, beginAtZero: true }
            }
        }
    })
}
</script>

<style scoped>
.category-page { padding-top: .5rem; }
.header { margin-bottom: .75rem; }

.grid {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 1rem;
}

.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.card-header { padding: .6rem .9rem; border-bottom: 1px solid #e5e7eb; }
.card-title { font-size: 1.05rem; margin: 0; }
.card-body { padding: .9rem; }

.kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.kpi { background: #f9fafb; border: 1px solid #eef2f7; border-radius: 10px; padding: .6rem .7rem; }
.kpi-label { color: #6b7280; font-size: .8rem; }
.kpi-value { font-size: 1.05rem; font-weight: 600; }

.top-list { list-style: none; padding: 0; margin: 0; }
.top-list li { display: flex; align-items: center; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed #eee; }
.top-list li:last-child { border-bottom: none; }
.dot { width: 8px; height: 8px; border-radius: 999px; background: #36A2EB; display: inline-block; margin-right: .5rem; }
.desc { flex: 1; margin-left: .5rem; }
.amount { font-weight: 600; }

.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border-bottom: 1px solid #eee; padding: .55rem .5rem; }
.table th { text-align: left; color: #6b7280; font-weight: 600; font-size: .9rem; }
.text-end { text-align: right; }

.chart { width: 100%; height: 320px; }
.empty { color: #6b7280; font-style: italic; }

@media (max-width: 992px) {
    .grid { grid-template-columns: 1fr; }
}
</style>
