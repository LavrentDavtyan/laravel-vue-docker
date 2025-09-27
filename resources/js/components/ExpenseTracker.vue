<template>
  <div class="expense-tracker">
    <h2>Expense Tracker</h2>
    <router-link to="/expenses/create" class="btn btn-primary" style="margin-bottom:1rem;">Add Expense</router-link>

    <div class="filters">
      <input v-model="filters.category" type="text" placeholder="Filter by category" />
      <input v-model="filters.date" type="date" placeholder="Filter by date" />
      <button @click="fetchExpenses">Apply Filters</button>
      <button @click="clearFilters">Clear</button>
    </div>


      <!--  NEW: Reports Summary Panel -->
      <div class="report-panel">
          <h3>Summary Report</h3>
          <div class="report-filters">
              <label>Start: <input type="date" v-model="reportStart" /></label>
              <label>End: <input type="date" v-model="reportEnd" /></label>
              <button @click="loadSummary">Load Summary</button>
          </div>

          <div v-if="summary" class="report-results">
              <div><strong>Range:</strong> {{ summary.range.start }} → {{ summary.range.end }}</div>
              <div><strong>Total:</strong> {{ summary.total }}</div>
              <div><strong>Count:</strong> {{ summary.count }}</div>
              <div><strong>Days:</strong> {{ summary.days }}</div>
              <div><strong>Avg per day:</strong> {{ summary.avg_per_day }}</div>
              <div><strong>Avg per transaction:</strong> {{ summary.avg_per_tx }}</div>
          </div>
      </div>
      <!--  END NEW -->

    <div class="chart-section">
      <h3>Expenses by Category</h3>
      <canvas ref="chartCanvas" width="400" height="300"></canvas>
    </div>

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
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from '../http'
import Chart from 'chart.js/auto'


const expenses = ref([])
const filters = ref({ category: '', date: '' })
const chartCanvas = ref(null)
let chartInstance = null

//  NEW: Reports state
const reportStart = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)) // first day of month
const reportEnd = ref(new Date().toISOString().slice(0, 10)) // today
const summary = ref(null)

const loadSummary = async () => {
    try {
        const { data } = await axios.get('/reports/summary', {
            params: { start: reportStart.value, end: reportEnd.value }
        })
        summary.value = data

        // also load category share at the same time
        await loadCategoryShare()
    } catch (e) {
        console.error('Summary error:', e?.response?.data || e.message)
        alert('Failed to load summary. Check console.')
    }
}



const categoryData = ref([])

async function loadCategoryShare() {
    try {
        const { data } = await axios.get('/reports/category-share', {
            params: { start: reportStart.value, end: reportEnd.value }
        })
        categoryData.value = data.categories
        renderChart() // re-render chart with new data
    } catch (e) {
        console.error('Category share error:', e?.response?.data || e.message)
        alert('Failed to load category share. Check console.')
    }
}
//  END NEW

const fetchExpenses = async () => {
  let params = {}
  if (filters.value.category) params.category = filters.value.category
  if (filters.value.date) params.date = filters.value.date
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

    const labels = categoryData.value.map(c => c.category)
    const data = categoryData.value.map(c => c.total)

    if (!labels.length || !data.length) {
        console.warn("No category data, skipping chart render")
        return
    }

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
                ],
            }]
        },
        options: {
            responsive: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    })
}



onMounted(() => {
  fetchExpenses()
    loadCategoryShare()
})

// watch(expenses, () => {
//   renderChart()
// })
</script>

<style scoped>
.expense-tracker {
  max-width: 700px;
  margin: 2rem auto;
  background: #fff;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
.filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.report-panel { /*  styling for reports */
    margin: 1rem 0;
    padding: 1rem;
    border: 1px solid #eee;
    border-radius: 8px;
    background: #fafafa;
}
.report-filters {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}
.report-results div {
    margin: 0.25rem 0;
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
.chart-section {
  margin-top: 2rem;
}
.btn {
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  border: none;
  margin-right: 0.25rem;
  cursor: pointer;
}
.btn-primary {
  background: #36A2EB;
  color: #fff;
}
.btn-secondary {
  background: #6c757d;
  color: #fff;
}
.btn-danger {
  background: #dc3545;
  color: #fff;
}
.btn-sm {
  font-size: 0.9em;
  padding: 0.15rem 0.5rem;
}
</style>
