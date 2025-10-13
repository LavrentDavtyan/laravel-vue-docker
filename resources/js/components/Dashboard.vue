<template>
    <div class="dashboard">
        <div class="dashboard-content">
            <div class="container">

                <!-- HEADER -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h1 class="mb-0">Dashboard</h1>

                    <!-- Small profile card -->
                    <div class="profile-card d-none d-md-flex">
                        <div class="me-3">
                            <div class="fw-semibold">{{ userFullName }}</div>
                            <div class="small text-muted">{{ currentUser?.email }}</div>
                        </div>
                        <span class="badge" :class="currentUser?.is_active ? 'text-bg-success' : 'text-bg-danger'">
                        {{ currentUser?.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- SNAPSHOT + RIGHT SIDEBAR -->
                <div class="row g-3">
                    <!-- LEFT: snapshot, quick stats, mini charts -->
                    <div class="col-12 col-xl-8">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-xl-4">
                                <div class="tile">
                                    <div class="tile-label">MTD Incomes</div>
                                    <div class="tile-value">{{ money(mtd.incomes) }}</div>
                                    <div class="tile-sub">All time: {{ money(total.incomes) }}</div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-xl-4">
                                <div class="tile">
                                    <div class="tile-label">MTD Expenses</div>
                                    <div class="tile-value text-danger">{{ money(mtd.expenses) }}</div>
                                    <div class="tile-sub">All time: {{ money(total.expenses) }}</div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-xl-4">
                                <div class="tile">
                                    <div class="tile-label">MTD Net Balance</div>
                                    <div class="tile-value" :class="mtd.net >= 0 ? 'text-success' : 'text-danger'">
                                        {{ money(mtd.net) }}
                                    </div>
                                    <div class="tile-sub">Overall: {{ money(overall.net) }}</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="tile">
                                    <div class="tile-label">This Month vs Last (Expenses)</div>
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="tile-value text-danger">{{ money(mtd.expenses) }}</div>
                                        <div class="text-end small">
                                            Last: {{ money(lastMonth.expenses) }}<br>
                                            <span :class="expDeltaPct >= 0 ? 'text-danger' : 'text-success'">
                                                {{ signedPct(expDeltaPct) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="tile">
                                    <div class="tile-label">This Month vs Last (Incomes)</div>
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="tile-value">{{ money(mtd.incomes) }}</div>
                                        <div class="text-end small">
                                            Last: {{ money(lastMonth.incomes) }}<br>
                                            <span :class="incDeltaPct >= 0 ? 'text-success' : 'text-danger'">
                                                {{ signedPct(incDeltaPct) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="tile">
                                    <div class="tile-label">Top Spending Category (This Month)</div>
                                    <div class="tile-value small mb-1">{{ topCategory.name || '—' }}</div>
                                    <div class="tile-sub">Spent: {{ money(topCategory.total) }}</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="tile">
                                    <div class="tile-label">Days Left & Forecast</div>
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div>
                                            <div class="tile-value small mb-1">{{ days.left }} days left</div>
                                            <div class="tile-sub">Avg/day: {{ money(days.avgPerDay) }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">Forecasted spend</div>
                                            <div class="fw-semibold">{{ money(days.forecast) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MINI CHARTS -->
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <div class="tile">
                                    <div class="tile-label">Net Balance Trend (30 days)</div>
                                    <canvas ref="sparkRef" height="110"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="tile">
                                <div class="tile-label">Expenses vs Incomes (30 days)</div>
                                <canvas ref="barRef" height="410"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: at-a-glance donut + ratios -->
                    <div class="col-12 col-xl-4">
                        <div class="tile h-100">
                            <div class="tile-label d-flex align-items-center justify-content-between">
                                At a glance
                                <span class="badge rounded-pill bg-light text-dark fw-normal">MTD</span>
                            </div>

                            <div class="donut-wrap">
                                <div class="donut-center">
                                    <div class="center-top">{{ money(mtd.net) }}</div>
                                    <div class="center-sub" :class="mtd.net >= 0 ? 'text-success' : 'text-danger'">
                                        {{ mtd.net >= 0 ? 'Net' : 'Short' }}
                                    </div>
                                </div>
                                <canvas ref="donutRef" height="180"></canvas>
                            </div>

                            <div class="legend mt-2">
                                <div class="legend-item">
                                    <span class="dot dot-exp"></span> Expenses — {{ money(mtd.expenses) }}
                                </div>
                                <div class="legend-item">
                                    <span class="dot dot-inc"></span> Incomes — {{ money(mtd.incomes) }}
                                </div>
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-6">
                                    <div class="mini-tile">
                                        <div class="mini-label">Savings rate</div>
                                        <div class="mini-value" :class="savingsRate >= 0 ? 'text-success' : 'text-danger'">
                                            {{ signedPct(savingsRate) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mini-tile">
                                        <div class="mini-label">Spend rate</div>
                                        <div class="mini-value text-danger">
                                            {{ signedPct(spendRate) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /RIGHT -->
                </div><!-- /row -->

                <!-- INSIGHTS (full width) -->
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <div class="tile insights-panel wide">
                            <div class="panel-header d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="tile-label mb-0">Insights</div>
                                    <div class="panel-sub small text-muted">Top overspends · This week</div>
                                </div>
                            </div>
                            <hr class="panel-sep" />
                            <div class="helper-cards">
                                <HelperCards />
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /container -->
        </div>
    </div>
</template>

<script>
import { computed, onMounted, ref, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/authStore'
import axios from '../http'
import Chart from 'chart.js/auto'
import HelperCards from './HelperCards.vue'

export default {

    name: 'Dashboard',
    components: { HelperCards },
    setup () {
        const authStore = useAuthStore()
        const currentUser = computed(() => authStore.currentUser)
        const userFullName = computed(() => authStore.userFullName)

        const mtd = ref({ incomes: 0, expenses: 0, net: 0 })
        const total = ref({ incomes: 0, expenses: 0 })
        const overall = ref({ net: 0 })
        const lastMonth = ref({ incomes: 0, expenses: 0 })
        const topCategory = ref({ name: '', total: 0 })
        const days = ref({ left: 0, avgPerDay: 0, forecast: 0 })

        const sparkRef = ref(null)
        const barRef = ref(null)
        const donutRef = ref(null)
        let spark, bar, donut

        const money = (n, cur = 'USD') => {
            const v = Number(n ?? 0)
            try { return new Intl.NumberFormat(undefined, { style: 'currency', currency: cur }).format(v) }
            catch { return `${v.toFixed(2)} ${cur}` }
        }
        const signedPct = n => `${n > 0 ? '+' : ''}${n.toFixed(1)}%`

        const savingsRate = computed(() => {
            if (!mtd.value.incomes) return 0
            return ((mtd.value.incomes - mtd.value.expenses) / mtd.value.incomes) * 100
        })
        const spendRate = computed(() => {
            if (!mtd.value.incomes) return 0
            return (mtd.value.expenses / mtd.value.incomes) * 100
        })

        const ymd = d => {
            const y = d.getFullYear()
            const m = String(d.getMonth() + 1).padStart(2, '0')
            const day = String(d.getDate()).padStart(2, '0')
            return `${y}-${m}-${day}`
        }
        const monthBounds = (dt = new Date()) => {
            const from = new Date(dt.getFullYear(), dt.getMonth(), 1)
            const to = new Date(dt.getFullYear(), dt.getMonth() + 1, 0)
            return { from: ymd(from), to: ymd(to) }
        }
        const lastMonthBounds = () => {
            const now = new Date()
            const d = new Date(now.getFullYear(), now.getMonth() - 1, 1)
            const from = new Date(d.getFullYear(), d.getMonth(), 1)
            const to = new Date(d.getFullYear(), d.getMonth() + 1, 0)
            return { from: ymd(from), to: ymd(to) }
        }

        async function fetchSum (endpoint, q = {}) {
            const res = await axios.get(endpoint, { params: q })
            const arr = Array.isArray(res.data) ? res.data : []
            return arr.reduce((s, r) => s + (Number(r.amount) || 0), 0)
        }

        async function fetchGroupByCategory (q = {}) {
            const res = await axios.get('/expenses', { params: q })
            const map = (Array.isArray(res.data) ? res.data : []).reduce((acc, e) => {
                const k = e.category || 'Uncategorized'
                acc[k] = (acc[k] || 0) + (Number(e.amount) || 0)
                return acc
            }, {})
            let top = { name: '', total: 0 }
            Object.entries(map).forEach(([k, v]) => { if (v > top.total) top = { name: k, total: v } })
            return top
        }

        async function loadSnapshot () {
            const { from, to } = monthBounds()
            const { from: lf, to: lt } = lastMonthBounds()

            const [incM, expM] = await Promise.all([
                fetchSum('/incomes', { date_from: from, date_to: to }),
                fetchSum('/expenses', { date_from: from, date_to: to })
            ])
            mtd.value = { incomes: incM, expenses: expM, net: incM - expM }

            const [incAll, expAll] = await Promise.all([ fetchSum('/incomes'), fetchSum('/expenses') ])
            total.value = { incomes: incAll, expenses: expAll }
            overall.value = { net: incAll - expAll }

            const [incLast, expLast] = await Promise.all([
                fetchSum('/incomes', { date_from: lf, date_to: lt }),
                fetchSum('/expenses', { date_from: lf, date_to: lt })
            ])
            lastMonth.value = { incomes: incLast, expenses: expLast }

            topCategory.value = await fetchGroupByCategory({ date_from: from, date_to: to })

            const today = new Date()
            const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0)
            const daysPassed = today.getDate()
            const totalDays = monthEnd.getDate()
            const daysLeft = Math.max(0, totalDays - daysPassed)
            const avgPerDay = daysPassed ? mtd.value.expenses / daysPassed : 0
            const forecast = mtd.value.expenses + avgPerDay * daysLeft
            days.value = { left: daysLeft, avgPerDay, forecast }

            await drawCharts()
        }

        async function drawCharts () {
            // 30-day lines/bars
            const end = new Date()
            const start = new Date()
            start.setDate(end.getDate() - 29)
            const params = { date_from: ymd(start), date_to: ymd(end) }

            const [expRes, incRes] = await Promise.all([
                axios.get('/expenses', { params }),
                axios.get('/incomes', { params })
            ])
            const exps = Array.isArray(expRes.data) ? expRes.data : []
            const incs = Array.isArray(incRes.data) ? incRes.data : []

            const daysArr = []
            const netSeries = []
            const expSeries = []
            const incSeries = []

            let runningNet = 0
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                const key = ymd(d)
                const e = exps.filter(r => String(r.date).slice(0, 10) === key)
                    .reduce((s, r) => s + (Number(r.amount) || 0), 0)
                const i = incs.filter(r => String(r.date).slice(0, 10) === key)
                    .reduce((s, r) => s + (Number(r.amount) || 0), 0)
                runningNet += (i - e)
                daysArr.push(key.slice(5))
                netSeries.push(runningNet)
                expSeries.push(e)
                incSeries.push(i)
            }

            if (spark) spark.destroy()
            spark = new Chart(sparkRef.value, {
                type: 'line',
                data: { labels: daysArr, datasets: [{ data: netSeries, borderWidth: 2, fill: false }] },
                options: {
                    responsive: true, plugins: { legend: { display: false } },
                    scales: { x: { display: false }, y: { display: false } },
                    elements: { point: { radius: 0 } }
                }
            })

            if (bar) bar.destroy()
            bar = new Chart(barRef.value, {
                type: 'bar',
                data: {
                    labels: daysArr,
                    datasets: [
                        { label: 'Expenses', data: expSeries, borderWidth: 1, backgroundColor: '#FF6384' },
                        { label: 'Incomes',  data: incSeries, borderWidth: 1, backgroundColor: '#36A2EB' }
                    ]
                },
                options: { responsive: true, scales: { x: { display: true }, y: { beginAtZero: true, display: true } } }
            })

            // Donut: MTD mix
            const exp = Math.max(0, Number(mtd.value.expenses) || 0)
            const inc = Math.max(0, Number(mtd.value.incomes)  || 0)
            if (donut) donut.destroy()
            donut = new Chart(donutRef.value, {
                type: 'doughnut',
                data: {
                    labels: ['Expenses', 'Incomes'],
                    datasets: [{
                        data: [exp, inc],
                        backgroundColor: ['#FF6384', '#36A2EB'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: { legend: { display: false }, tooltip: { enabled: true } }
                }
            })
        }

        async function refreshAll () { await loadSnapshot() }
        const onFocus = () => refreshAll()
        const onVisible = () => { if (document.visibilityState === 'visible') refreshAll() }

        onMounted(() => {
            refreshAll()
            window.addEventListener('focus', onFocus)
            document.addEventListener('visibilitychange', onVisible)
        })
        onUnmounted(() => {
            window.removeEventListener('focus', onFocus)
            document.removeEventListener('visibilitychange', onVisible)
            if (spark) spark.destroy()
            if (bar) bar.destroy()
            if (donut) donut.destroy()
        })

        return {
            currentUser, userFullName,
            mtd, total, overall, lastMonth, topCategory, days,
            money, signedPct, savingsRate, spendRate,
            sparkRef, barRef, donutRef,
            expDeltaPct: computed(() => {
                const base = lastMonth.value.expenses || 0
                if (!base) return 100
                return ((mtd.value.expenses - base) / base) * 100
            }),
            incDeltaPct: computed(() => {
                const base = lastMonth.value.incomes || 0
                if (!base) return 100
                
                return ((mtd.value.incomes - base) / base) * 100
            })
        }
    }
}
</script>

<style scoped>
.dashboard { min-height: 100vh; background-color: #f8f9fa; }
.dashboard-content { padding: 1.5rem; }
.container { max-width: 1200px; margin: 0 auto; }

/* profile card */
.profile-card {
    background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
    padding: .75rem 1rem; align-items: center; gap: .75rem;
}
.profile-card .fw-semibold { font-size: .95rem; }
.profile-card .small { font-size: .8rem; }
.profile-card .badge { font-size: .75rem; padding: .35rem .5rem; border-radius: 999px; }

/* tiles */
.row.g-3 { --bs-gutter-y: 1rem; --bs-gutter-x: 1rem; }
.tile {
    background: #fff; border: 1px solid #e9ecef; border-radius: 14px;
    padding: 1rem 1.25rem; height: 100%;
    box-shadow: 0 4px 10px rgba(16,24,40,.04);
    transition: box-shadow .2s ease, transform .2s ease;
}
.tile:hover { box-shadow: 0 8px 18px rgba(16,24,40,.08); transform: translateY(-1px); }
.tile-label { font-size: .9rem; color: #6b7280; font-weight: 600; letter-spacing: .2px; }
.tile-value { font-size: 1.6rem; font-weight: 800; line-height: 1.1; color: #111827; }
@media (max-width: 576px) { .tile-value { font-size: 1.35rem; } }
.tile-sub { font-size: .85rem; color: #6b7280; }

/* charts */
canvas { width: 100% !important; }
.tile canvas { background: linear-gradient(180deg,#ffffff 0%,#fafafa 100%); border-radius: 8px; }

/* donut block */
.donut-wrap { position: relative; width: 100%; max-width: 320px; margin: .5rem auto 0; }
.donut-wrap canvas { display: block; margin: 0 auto; }
.donut-center {
    position: absolute; inset: 0; display: grid; place-items: center;
    pointer-events: none; text-align: center;
}
.donut-center .center-top { font-weight: 800; font-size: 1.05rem; line-height: 1; }
.donut-center .center-sub { font-size: .8rem; margin-top: .15rem; }

/* legend and minis */
.legend { display: flex; gap: 1rem; flex-wrap: wrap; }
.legend-item { font-size: .85rem; color: #475569; }
.dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; margin-right: .4rem; }
.dot-exp { background: #FF6384; }
.dot-inc { background: #36A2EB; }

.mini-tile {
    background: #fafbfc; border: 1px solid #eef2f7; border-radius: 12px;
    padding: .6rem .75rem; height: 100%;
}
.mini-label { font-size: .8rem; color: #6b7280; }
.mini-value { font-weight: 800; font-size: 1.05rem; }

/* insights (full width) */
.insights-panel { display: flex; flex-direction: column; }
.insights-panel .panel-header { padding-bottom: .25rem; }
.insights-panel .panel-sub { margin-top: .1rem; }
.insights-panel .panel-sep { border: 0; border-top: 1px solid #eef2f7; margin: .25rem 0 .75rem; }

/* HelperCards inside */
.insights-panel .helper-cards .row { --bs-gutter-y: 1rem; --bs-gutter-x: 1rem; }
.insights-panel .helper-cards .card { border-radius: 12px; border-color: #eef2f7; }
.insights-panel .helper-cards .badge { font-size: .7rem; padding: .25rem .4rem; }
.insights-panel .helper-cards .btn { padding: .25rem .6rem; }

/* headers */
h1.mb-0 { font-weight: 800; letter-spacing: .3px; }
</style>
