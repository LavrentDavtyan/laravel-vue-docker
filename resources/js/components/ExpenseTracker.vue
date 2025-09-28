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

        <div class="filters pt-5">
            <input v-model="filters.category" type="text" class="form-control" placeholder="Filter by category" />
            <input v-model="filters.date" type="date" class="form-control" placeholder="Filter by date" />
            <button @click="fetchExpenses" class="btn btn-success">Apply Filters</button>
            <button @click="clearFilters" class="btn btn-secondary">Clear</button>
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
                <td>{{ expense.category }}</td>
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
                <ExportExcelButton />
            </div>
      </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '../http'
import Chart from 'chart.js/auto'
import ExportExcelButton from './ExportExcelButton.vue'

const expenses = ref([])
const filters = ref({ category: '', date: '' })
const chartCanvas = ref(null)
let chartInstance = null
const lineChartCanvas = ref(null)
let lineChartInstance = null

const fetchExpenses = async () => {
    const params = {}
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.date)     params.date     = filters.value.date
    const res = await axios.get('/expenses', { params })
    expenses.value = res.data
}

const deleteExpense = async (id) => {
    if (confirm('Delete this expense?')) {
        await axios.delete(`/expenses/${id}`)
        fetchExpenses()
    }
}

const clearFilters = () => {
    filters.value = { category: '', date: '' }
    fetchExpenses()
}

const renderChart = () => {
    if (!chartCanvas.value) return
    if (chartInstance) {
        chartInstance.destroy()
    }

    // compute category totals on the client from expenses
    const grouped = expenses.value.reduce((acc, exp) => {
        acc[exp.category] = (acc[exp.category] || 0) + Number(exp.amount)
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
    const date = exp.date // should be YYYY-MM-DD
    acc[date] = (acc[date] || 0) + Number(exp.amount)
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
const confirmExport = async () => {
    const ok = confirm('Generate Excel from current filters? Click OK to download.')
    if (!ok) return
    await exportToExcel()
}

const exportExcel = async () => {
    try {
        // pass current filters if set (so export matches the table)
        const params = {}
        if (filters.value.category) params.category = filters.value.category
        if (filters.value.date)     params.date     = filters.value.date
        // optional date range support if you add inputs:
        // if (reportStart.value) params.start = reportStart.value
        // if (reportEnd.value)   params.end   = reportEnd.value

        // IMPORTANT: responseType blob
        const res = await axios.get('/exports/expenses', {
            params,
            responseType: 'blob'
        })

        // Try to read filename from headers
        const dispo = res.headers['content-disposition'] || ''
        const match = dispo.match(/filename="?([^"]+)"?/i)
        const filename = match ? match[1] : `expenses_${Date.now()}.xlsx`

        // Create a temporary link and download
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(url)

        // tiny success notice
        alert('Excel is ready and downloading…')
    } catch (e) {
        console.error('Export error:', e?.response || e)
        alert('Failed to export. Check console for details.')
    }
}


onMounted(() => {
    fetchExpenses()
})

watch(expenses, renderChart)
</script>

<style scoped>
.filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    /* flex-wrap: wrap; */
}
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
