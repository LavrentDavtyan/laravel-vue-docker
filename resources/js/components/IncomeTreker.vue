<template>
  <div class="income-tracker">
    <div class="row">
      <div class="col-10">
        <h2>Income Tracker</h2>
      </div>
      <div class="col-2 text-end">
        <router-link to="/incomes/create" class="btn btn-primary" style="margin-bottom:1rem;">
          Add Income
        </router-link>
      </div>
    </div>

    <div class="filters pt-5">
      <input v-model="filters.category" type="text" class="form-control" placeholder="Filter by category" />
      <input v-model="filters.date" type="date" class="form-control" placeholder="Filter by date" />
      <button @click="fetchIncomes" class="btn btn-success">Apply Filters</button>
      <button @click="clearFilters" class="btn btn-secondary">Clear</button>
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
            <td>{{ income.category }}</td>
            <td>{{ income.description }}</td>
            <td>{{ income.date }}</td>
            <td>
              <router-link :to="`/incomes/${income.id}/edit`" class="btn btn-sm btn-secondary">Edit</router-link>
              <button @click="deleteIncome(income.id)" class="btn btn-sm btn-danger">Delete</button>
            </td>
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
import axios from '../http'
import Chart from 'chart.js/auto'
import ExportExcel from './ExportExcel.vue'

const incomes = ref([])
const filters = ref({ category: '', date: '' })
const chartCanvas = ref(null)
let chartInstance = null
const lineChartCanvas = ref(null)
let lineChartInstance = null

const fetchIncomes = async () => {
  const params = {}
  if (filters.value.category) params.category = filters.value.category
  if (filters.value.date) params.date = filters.value.date
  const res = await axios.get('/incomes', { params })
  incomes.value = res.data
}

const deleteIncome = async (id) => {
  if (confirm('Delete this income?')) {
    await axios.delete(`/incomes/${id}`)
    fetchIncomes()
  }
}

const clearFilters = () => {
  filters.value = { category: '', date: '' }
  fetchIncomes()
}

const renderChart = () => {
  if (!chartCanvas.value) return
  if (chartInstance) chartInstance.destroy()

  // Group by category
  const grouped = incomes.value.reduce((acc, inc) => {
    acc[inc.category] = (acc[inc.category] || 0) + Number(inc.amount)
    return acc
  }, {})
  const labels = Object.keys(grouped)
  const data = Object.values(grouped)

  if (labels.length) {
    chartInstance = new Chart(chartCanvas.value, {
      type: 'pie',
      data: {
        labels,
        datasets: [{
          label: 'Incomes by Category',
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
  }

  // --- Line chart by date ---
  if (!lineChartCanvas.value) return
  if (lineChartInstance) lineChartInstance.destroy()

  const groupedByDate = incomes.value.reduce((acc, inc) => {
    const date = inc.date
    acc[date] = (acc[date] || 0) + Number(inc.amount)
    return acc
  }, {})

  const sortedDates = Object.keys(groupedByDate).sort()
  const dateTotals = sortedDates.map(d => groupedByDate[d])

  if (sortedDates.length) {
    lineChartInstance = new Chart(lineChartCanvas.value, {
      type: 'line',
      data: {
        labels: sortedDates,
        datasets: [{
          label: 'Total Incomes per Day',
          data: dateTotals,
          fill: false,
          borderColor: '#4BC0C0',
          tension: 0.3,
          pointBackgroundColor: '#4BC0C0'
        }]
      },
      options: {
        responsive: false,
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: {
          x: { title: { display: true, text: 'Date' } },
          y: { title: { display: true, text: 'Amount' }, beginAtZero: true }
        }
      }
    })
  }
}

onMounted(() => {
  fetchIncomes()
})

watch(incomes, renderChart)
</script>

<style scoped>
.filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
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
