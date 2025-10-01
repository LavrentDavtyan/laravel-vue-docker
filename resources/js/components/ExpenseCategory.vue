<template>
  <div class="category-page">
    <div class="header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <router-link to="/expenses" class="btn btn-sm btn-secondary">← Back</router-link>
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
              <div class="kpi-label">Total Spend</div>
              <div class="kpi-value">{{ currency(totalSpend) }}</div>
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

          <div class="mt-3">
            <h4 class="sub">Top Descriptions</h4>
            <ul class="top-list" v-if="top.length">
              <li v-for="t in top" :key="t.description">
                <span class="dot"></span>
                <span class="desc">{{ t.description || '—' }}</span>
                <span class="amount">{{ currency(Number(t.total)) }}</span>
              </li>
            </ul>
            <div v-else class="empty">No top descriptions in this range.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Items</h3>
        <small class="text-muted">Sorted by date (newest first)</small>
      </div>
      <div class="card-body">
        <div v-if="!items.data?.length" class="empty">No items in this range.</div>
        <table v-else class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th class="text-end">Amount</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items.data" :key="item.id">
              <td>{{ item.date }}</td>
              <td class="text-end">{{ currency(Number(item.amount)) }}</td>
              <td>{{ item.description }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from '../http'
import Chart from 'chart.js/auto'

const route = useRoute()
const slug = route.params.slug
const trendChart = ref(null)
const top = ref([])
const items = ref({ data: [] })
const chartInstance = ref(null)

const rangeLabel = computed(() => {
  const q = route.query
  if (q.date_from && q.date_to) return `${q.date_from} → ${q.date_to}`
  return 'last 90 days'
})

const currency = (n) => {
  const value = isFinite(n) ? Number(n) : 0
  try { return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(value) } catch { return new Intl.NumberFormat().format(value) }
}

const totalSpend = computed(() => items.value.data?.reduce((s, it) => s + Number(it.amount || 0), 0) || 0)
const itemsTotal = computed(() => items.value.data?.length || 0)
const avgPerDay = computed(() => {
  const q = route.query
  const from = q.date_from ? new Date(q.date_from) : null
  const to = q.date_to ? new Date(q.date_to) : null
  const days = (from && to) ? Math.max(1, Math.round((to - from) / 86400000) + 1) : 90
  return totalSpend.value / days
})

onMounted(async () => {
  try {
    const res = await axios.get(`/expenses/category/${slug}`, { params: route.query })
    top.value = res.data.top
    items.value = res.data.items

    const labels = res.data.trend.map(t => t.date)
    const data = res.data.trend.map(t => Number(t.total))

    // destroy previous if exists
    if (chartInstance.value) chartInstance.value.destroy()

    chartInstance.value = new Chart(trendChart.value, {
      type: 'line',
      data: { labels, datasets: [{ label: 'Total', data, fill: false }] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true, position: 'top' },
          tooltip: { mode: 'index', intersect: false }
        },
        interaction: { mode: 'nearest', intersect: false },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { callback: (v) => new Intl.NumberFormat().format(v) } }
        }
      }
    })
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || 'Category report failed')
  }
})
</script>

<style scoped>
.category-page { padding-bottom: 1rem; }
.header { margin-bottom: 0.75rem; }
.grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 1rem; }

.card { background: #fff; border: 1px solid #eee; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.card-header { padding: .75rem 1rem; border-bottom: 1px solid #eee; background: #fafafa; }
.card-title { margin: 0; font-size: 1.05rem; font-weight: 600; }
.card-body { padding: 1rem; }

.kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.kpi { background: #f7f9fc; border: 1px solid #eef1f6; border-radius: 8px; padding: .6rem .75rem; }
.kpi-label { color: #6b7280; font-size: .8rem; }
.kpi-value { font-size: 1.15rem; font-weight: 700; margin-top: .15rem; }

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
