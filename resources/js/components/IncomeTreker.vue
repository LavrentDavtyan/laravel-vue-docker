<template>
    <div class="expense-tracker">
        <div class="row">
            <div class="col-10">
                <h2>Income Tracker</h2>
            </div>
            <div class="col-2 text-end">
                <router-link to="/incomes/create" class="btn btn-primary" style="margin-bottom:1rem;">Add Income</router-link>
            </div>
        </div>

        <div class="filters pt-5 d-flex align-items-end gap-2 flex-wrap">
            <input v-model="filters.category" type="text" class="form-control" placeholder="Filter by category" style="max-width: 200px;" />
            <input v-model="filters.date_from" type="date" class="form-control" placeholder="From" style="max-width: 180px;" />
            <input v-model="filters.date_to" type="date" class="form-control" placeholder="To" style="max-width: 180px;" />
            <button @click="applyFilters" class="btn btn-success">Apply Filters</button>
            <button @click="clearFilters" class="btn btn-secondary">Clear</button>
        </div>

        <div class="presets-row d-flex gap-2 mt-3 flex-wrap">
            <button @click="setPreset('today')"  :class="['btn','btn-sm', activePreset==='today'  ? 'btn-primary' : 'btn-light']">Today</button>
            <button @click="setPreset('week')"   :class="['btn','btn-sm', activePreset==='week'   ? 'btn-primary' : 'btn-light']">This Week</button>
            <button @click="setPreset('month')"  :class="['btn','btn-sm', activePreset==='month'  ? 'btn-primary' : 'btn-light']">This Month</button>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="chart-section">
                    <h3>Incomes by Category</h3>
                    <canvas ref="chartCanvas" width="400" height="300"></canvas>
                </div>
            </div>
            <div class="col-6">
                <div class="chart-section">
                    <h3>Incomes per Day</h3>
                    <canvas ref="lineChartCanvas" width="650" height="300"></canvas>
                </div>
            </div>
        </div>

        <div>
            <table>
                <thead>
                <tr>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="income in incomes" :key="income.id">
                    <td>{{ income.amount }}</td>
                    <td>
                        <router-link :to="{ path: `/incomes/category/${income.category}`, query: route.query }">
                            {{ income.category }}
                        </router-link>
                    </td>
                    <td>{{ income.description }}</td>
                    <td>{{ income.date }}</td>
                    <td>
                        <router-link :to="`/incomes/${income.id}/edit`" class="btn btn-sm btn-secondary">Edit</router-link>
                        <button @click="deleteIncome(income.id)" class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
                <tr v-if="!incomes.length">
                    <td colspan="5" class="text-center text-muted">No incomes found.</td>
                </tr>
                </tbody>
            </table>
            <div class="mt-3">
                <ExportExcel type="incomes" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '../http'
import Chart from 'chart.js/auto'
import ExportExcel from './ExportExcel.vue'

const route = useRoute()
const router = useRouter()

const filters = ref({
    category : route.query.category  ? String(route.query.category)  : '',
    date_from: route.query.date_from ? String(route.query.date_from) : '',
    date_to  : route.query.date_to   ? String(route.query.date_to)   : ''
})

if (!route.query.date_from && !route.query.date_to) {
    const today = new Date()
    const start = new Date(today.getFullYear(), today.getMonth(), 1)
    filters.value.date_from = fmtLocalYMD(start)
    filters.value.date_to   = endOfMonth(today)
    router.replace({ query: { ...route.query, date_from: filters.value.date_from, date_to: filters.value.date_to } })
}

const activePreset = ref('month')

const incomes = ref([])
const chartCanvas = ref(null)
let chartInstance = null
const lineChartCanvas = ref(null)
let lineChartInstance = null

function fmtLocalYMD(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
function endOfMonth(d) {
  const e = new Date(d.getFullYear(), d.getMonth() + 1, 0)
  return fmtLocalYMD(e)
}
function toISODate(d) { return fmtLocalYMD(d) }

function setPreset(type) {
    const today = new Date()
    let from = '', to = ''
    switch (type) {
        case 'today': { from = toISODate(today); to = from; break }
        case 'week':  { const start = new Date(today); const weekday = today.getDay(); start.setDate(today.getDate() - weekday); from = toISODate(start); to = toISODate(today); break }
        case 'month': { const start = new Date(today.getFullYear(), today.getMonth(), 1); from = toISODate(start); to = endOfMonth(today); break }
    }
    filters.value.date_from = from
    filters.value.date_to   = to
    activePreset.value = type
    router.replace({ query: { ...route.query, category: filters.value.category || undefined, date_from: from || undefined, date_to: to || undefined } })
    fetchIncomes()
}

function applyFilters() {
    router.replace({ query: { ...route.query, category: filters.value.category || undefined, date_from: filters.value.date_from || undefined, date_to: filters.value.date_to || undefined } })
    fetchIncomes()
}

function clearFilters() {
    filters.value = { category: '', date_from: '', date_to: '' }
    activePreset.value = ''
    router.replace({ query: { ...route.query, category: undefined, date_from: undefined, date_to: undefined } })
    fetchIncomes()
}

const fetchIncomes = async () => {
    const params = {}
    if (filters.value.category)  params.category  = filters.value.category
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to)   params.date_to   = filters.value.date_to
    const res = await axios.get('/incomes', { params })
    incomes.value = res.data
}

const deleteIncome = async (id) => {
    if (confirm('Delete this income?')) {
        try {
            await axios.delete(`/incomes/${id}`)
            await fetchIncomes()
        } catch (e) {
            if (e?.response?.status === 401) {
                alert('Session expired. Please log in again.')
            } else {
                console.error(e)
                alert('Delete failed.')
            }
        }
    }
}

const renderChart = () => {
    if (!chartCanvas.value) return
    if (chartInstance) chartInstance.destroy()

    const grouped = incomes.value.reduce((acc, inc) => {
        const key = inc.category ?? 'Uncategorized'
        const val = Number(inc.amount) || 0
        acc[key] = (acc[key] || 0) + val
        return acc
    }, {})
    const labels = Object.keys(grouped)
    const data   = Object.values(grouped)
    if (!labels.length) return

    chartInstance = new Chart(chartCanvas.value, {
        type: 'pie',
        data: { labels, datasets: [{ label: 'Incomes by Category', data, backgroundColor: ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF'] }] },
        options: { responsive: false, plugins: { legend: { position: 'bottom' } } }
    })

    if (!lineChartCanvas.value) return
    if (lineChartInstance) lineChartInstance.destroy()

    const groupedByDate = incomes.value.reduce((acc, inc) => {
        const key = (inc.date || '').slice(0, 10)
        const val = Number(inc.amount) || 0
        acc[key] = (acc[key] || 0) + val
        return acc
    }, {})
    const sortedDates = Object.keys(groupedByDate).sort()
    const dateTotals  = sortedDates.map(d => groupedByDate[d])

    if (sortedDates.length) {
        lineChartInstance = new Chart(lineChartCanvas.value, {
            type: 'line',
            data: { labels: sortedDates, datasets: [{ label: 'Total Incomes per Day', data: dateTotals, fill: false, borderColor: '#36A2EB', tension: 0.3, pointBackgroundColor: '#36A2EB' }] },
            options: { responsive: false, plugins: { legend: { display: true, position: 'bottom' } }, scales: { x: { title: { display: true, text: 'Date' } }, y: { title: { display: true, text: 'Amount' }, beginAtZero: true } } }
        })
    }
}

watch(() => route.query, async () => {
    let category  = route.query.category ? String(route.query.category) : ''
    let date_from = route.query.date_from ? String(route.query.date_from) : ''
    let date_to   = route.query.date_to   ? String(route.query.date_to)   : ''

    // If user navigates to /incomes with no query, default to This Month (full month)
    if (!date_from && !date_to) {
        const today = new Date()
        const start = new Date(today.getFullYear(), today.getMonth(), 1)
        date_from = fmtLocalYMD(start)
        date_to   = endOfMonth(today)
        activePreset.value = 'month'   //  force month active by default
        const cur = { ...(route.query || {}) }
        if (cur.date_from !== date_from || cur.date_to !== date_to || (cur.category || '') !== category) {
            router.replace({ query: { category: category || undefined, date_from, date_to } }).catch(() => {})
        }
    }

    filters.value.category  = category
    filters.value.date_from = date_from
    filters.value.date_to   = date_to

    await fetchIncomes()
}, { immediate: true })

watch(() => incomes.value, () => renderChart())


</script>

<style scoped>
.filters { display:flex; flex-direction:row; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }
.presets-row { margin-bottom: .5rem; }

table { width:100%; border-collapse: collapse; margin-bottom:2rem; }
th, td { border: 1px solid #eee; padding: .5rem; text-align: left; }
.chart-section { margin-top: 2rem; }
.btn { padding: .25rem .75rem; border-radius:4px; border:none; margin-right:.25rem; cursor:pointer; }
.btn-primary { background:#36A2EB; color:#fff; }
.btn-secondary { background:#6c757d; color:#fff; }
.btn-danger { background:#dc3545; color:#fff; }
.btn-sm { font-size:.9em; padding:.15rem .5rem; }
</style>
