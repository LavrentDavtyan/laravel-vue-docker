<template>
    <div class="expense-tracker">
        <div class="row">
            <div class="col-10">
                <h2>Expense Tracker</h2>
            </div>
            <div class="col-2 text-end">
                <router-link to="/expenses/create" class="btn btn-primary" style="margin-bottom:1rem;">Add Expense</router-link>
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
          <button @click="setPreset('today')" :class="['btn','btn-sm', activePreset==='today' ? 'btn-primary' : 'btn-light']">Today</button>
          <button @click="setPreset('week')"  :class="['btn','btn-sm', activePreset==='week'  ? 'btn-primary' : 'btn-light']">This Week</button>
          <button @click="setPreset('month')" :class="['btn','btn-sm', activePreset==='month' ? 'btn-primary' : 'btn-light']">This Month</button>
          <button @click="setPreset('last30')" :class="['btn','btn-sm', activePreset==='last30'? 'btn-primary' : 'btn-light']">Last 30 days</button>
        </div>

        <div class="row">
          <div class="col-6">
            <div class="chart-section">
              <h3>Expenses by Category</h3>
              <canvas ref="chartCanvas" width="400" height="300"></canvas>
            </div>
          </div>
          <div class="col-6">
            <div class="chart-section">
              <h3>Expenses per Day</h3>
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
            <tr v-for="expense in expenses" :key="expense.id">
                <td>{{ expense.amount }}</td>
                <td>
                    <router-link :to="{ path: `/expenses/category/${expense.category}`, query: route.query }">{{ expense.category }}</router-link>
                </td>
                <td>{{ expense.description }}</td>
                <td>{{ expense.date }}</td>
                <td>
                    <router-link :to="`/expenses/${expense.id}/edit`" class="btn btn-sm btn-secondary">Edit</router-link>
                    <button @click="deleteExpense(expense.id)" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            </tbody>
        </table>
          <div class="mt-3">
              <ExportExcel type="expenses" />
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
    category: route.query.category ? String(route.query.category) : '',
    date_from: route.query.date_from ? String(route.query.date_from) : '',
    date_to: route.query.date_to ? String(route.query.date_to) : ''
})

if (!route.query.date_from && !route.query.date_to) {
  const today = new Date()
  const start = new Date(today)
  start.setDate(today.getDate() - 30)
  filters.value.date_from = start.toISOString().slice(0, 10)
  filters.value.date_to = today.toISOString().slice(0, 10)
  router.replace({ query: { ...route.query, date_from: filters.value.date_from, date_to: filters.value.date_to } })
}

const activePreset = ref('last30')

/** @type {import('vue').Ref<Array<{id:number|string, amount:number|string, category:string|null, description:string|null, date:string|null}>>} */
const expenses = ref([])
/** @type {import('vue').Ref<HTMLCanvasElement|null>} */
const chartCanvas = ref(null)
let chartInstance = null
/** @type {import('vue').Ref<HTMLCanvasElement|null>} */
const lineChartCanvas = ref(null)
let lineChartInstance = null

function toISODate(d) {
    return d.toISOString().slice(0, 10)
}

function setPreset(type) {
    const today = new Date()
    let from = '', to = ''
    switch (type) {
        case 'today':
            from = to = toISODate(today)
            break
        case 'week': {
            const start = new Date(today)
            const weekday = today.getDay() // 0=Sun
            start.setDate(today.getDate() - weekday)
            from = toISODate(start)
            to = toISODate(today)
            break
        }
        case 'month': {
            const start = new Date(today.getFullYear(), today.getMonth(), 1)
            from = toISODate(start)
            to = toISODate(today)
            break
        }
        case 'last30': {
            const start = new Date(today)
            start.setDate(today.getDate() - 30)
            from = toISODate(start)
            to = toISODate(today)
            break
        }
    }
    filters.value.date_from = from
    filters.value.date_to = to
    activePreset.value = type
    // sync to URL without reloading
    router.replace({ query: { ...route.query, category: filters.value.category || undefined, date_from: from || undefined, date_to: to || undefined } })
    fetchExpenses()
}

function applyFilters() {
    router.replace({ query: { ...route.query, category: filters.value.category || undefined, date_from: filters.value.date_from || undefined, date_to: filters.value.date_to || undefined } })
    fetchExpenses()
}


const fetchExpenses = async () => {
    const params = {}
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to)   params.date_to   = filters.value.date_to
    const res = await axios.get('/expenses', { params })
    expenses.value = res.data
}

const deleteExpense = async (id) => {
    if (confirm('Delete this expense?')) {
        try {
            await axios.delete(`/expenses/${id}`)
            await fetchExpenses() // wait for refresh to finish
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

const clearFilters = () => {
    filters.value = { category: '', date_from: '', date_to: '' }
    activePreset.value = ''
    router.replace({ query: { ...route.query, category: undefined, date_from: undefined, date_to: undefined } })
    fetchExpenses()
}

const renderChart = () => {
    if (!chartCanvas.value) return
    if (chartInstance) {
        chartInstance.destroy()
    }

    // compute category totals on the client from expenses
    const grouped = expenses.value.reduce((acc, exp) => {
      const key = exp.category ?? 'Uncategorized' // fix: avoid undefined/typos
      const val = Number(exp.amount) || 0         // fix: ensure numeric
      acc[key] = (acc[key] || 0) + val
      return acc
    }, {})
    const labels = Object.keys(grouped)
    const data = Object.values(grouped)

    if (!labels.length) return

    chartInstance = new Chart(chartCanvas.value, {
        type: 'pie',
        data: {
            labels,
            datasets: [{
                label: 'Expenses by Category',
                data,
                backgroundColor: [
                    '#36A2EB', '#FF6384', '#FFCE56',
                    '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: false,
            plugins: { legend: { position: 'bottom' } }
        }
    })

  // --- Line chart by day ---
  if (!lineChartCanvas.value) return
  if (lineChartInstance) lineChartInstance.destroy()

  // group by date
  const groupedByDate = expenses.value.reduce((acc, exp) => {
    const key = (exp.date || '').slice(0, 10)   // normalize YYYY-MM-DD
    const val = Number(exp.amount) || 0         // fix: ensure numeric
    acc[key] = (acc[key] || 0) + val
    return acc
  }, {})

  // sort by date
  const sortedDates = Object.keys(groupedByDate).sort()
  const dateTotals = sortedDates.map(d => groupedByDate[d])

  if (sortedDates.length) {
    lineChartInstance = new Chart(lineChartCanvas.value, {
      type: 'line',
      data: {
        labels: sortedDates,
        datasets: [{
          label: 'Total Expenses per Day',
          data: dateTotals,
          fill: false,
          borderColor: '#36A2EB',
          tension: 0.3,
          pointBackgroundColor: '#36A2EB'
        }]
      },
      options: {
        responsive: false,
        plugins: {
          legend: { display: true, position: 'bottom' }
        },
        scales: {
          x: { title: { display: true, text: 'Date' } },
          y: { title: { display: true, text: 'Amount' }, beginAtZero: true }
        }
      }
    })
  }
}

//  NEW: Export helpers
// const confirmExport = async () => {
//     const ok = confirm('Generate Excel from current filters? Click OK to download.')
//     if (!ok) return
//     await exportToExcel()
// }

// const exportExcel = async () => {
//     try {
//         // pass current filters if set (so export matches the table)
//         const params = {}
//         if (filters.value.category) params.category = filters.value.category
//         if (filters.value.date)     params.date     = filters.value.date
//         // optional date range support if you add inputs:
//         // if (reportStart.value) params.start = reportStart.value
//         // if (reportEnd.value)   params.end   = reportEnd.value

//         // IMPORTANT: responseType blob
//         const res = await axios.get('/exports/expenses', {
//             params,
//             responseType: 'blob'
//         })

//         // Try to read filename from headers
//         const dispo = res.headers['content-disposition'] || ''
//         const match = dispo.match(/filename="?([^"]+)"?/i)
//         const filename = match ? match[1] : `expenses_${Date.now()}.xlsx`

//         // Create a temporary link and download
//         const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
//         const url = window.URL.createObjectURL(blob)
//         const a = document.createElement('a')
//         a.href = url
//         a.download = filename
//         document.body.appendChild(a)
//         a.click()
//         a.remove()
//         window.URL.revokeObjectURL(url)

//         // tiny success notice
//         alert('Excel is ready and downloading…')
//     } catch (e) {
//         console.error('Export error:', e?.response || e)
//         alert('Failed to export. Check console for details.')
//     }
// }


onMounted(() => {
    fetchExpenses()
})

watch(() => route.query, (q) => {
    filters.value.category  = q.category ? String(q.category) : ''
    filters.value.date_from = q.date_from ? String(q.date_from) : ''
    filters.value.date_to   = q.date_to ? String(q.date_to) : ''
    fetchExpenses()
})


watch(() => expenses.value, () => renderChart())
</script>

<style scoped>
.filters {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.presets-row { margin-bottom: 0.5rem; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2rem;
}
th, td {
    border: 1px solid #eee;
    padding: 0.5rem;
    text-align: left;
}
.chart-section { margin-top: 2rem; }
.btn { padding: 0.25rem 0.75rem; border-radius: 4px; border: none; margin-right: 0.25rem; cursor: pointer; }
.btn-primary   { background: #36A2EB; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger    { background: #dc3545; color: #fff; }
.btn-sm { font-size: 0.9em; padding: 0.15rem 0.5rem; }
</style>
