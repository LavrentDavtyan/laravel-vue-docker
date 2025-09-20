<template>
  <div class="expense-tracker">
    <h2>Expense Tracker</h2>
    <form @submit.prevent="handleSubmit">
      <input v-model="form.amount" type="number" step="0.01" placeholder="Amount" required />
      <input v-model="form.category" type="text" placeholder="Category" required />
      <input v-model="form.description" type="text" placeholder="Description" />
      <input v-model="form.date" type="date" required />
      <button type="submit">{{ form.id ? 'Update' : 'Add' }} Expense</button>
      <button v-if="form.id" type="button" @click="resetForm">Cancel</button>
    </form>

    <div class="filters">
      <input v-model="filters.category" type="text" placeholder="Filter by category" />
      <input v-model="filters.date" type="date" placeholder="Filter by date" />
      <button @click="fetchExpenses">Apply Filters</button>
      <button @click="clearFilters">Clear</button>
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
            <button @click="editExpense(expense)">Edit</button>
            <button @click="deleteExpense(expense.id)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="chart-section">
      <h3>Expenses by Category</h3>
      <canvas ref="chartCanvas" width="400" height="300"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/authStore'
import Chart from 'chart.js/auto'

const authStore = useAuthStore()
const expenses = ref([])
const form = ref({ id: null, amount: '', category: '', description: '', date: '' })
const filters = ref({ category: '', date: '' })
const chartCanvas = ref(null)
let chartInstance = null

const fetchExpenses = async () => {
  let params = {}
  if (filters.value.category) params.category = filters.value.category
  if (filters.value.date) params.date = filters.value.date
  const res = await axios.get('/api/expenses', { params })
  expenses.value = res.data
}

const handleSubmit = async () => {
  if (form.value.id) {
    await axios.put(`/api/expenses/${form.value.id}`, form.value)
  } else {
    await axios.post('/api/expenses', form.value)
  }
  resetForm()
  fetchExpenses()
}

const editExpense = (expense) => {
  form.value = { ...expense }
}

const deleteExpense = async (id) => {
  if (confirm('Delete this expense?')) {
    await axios.delete(`/api/expenses/${id}`)
    fetchExpenses()
  }
}

const resetForm = () => {
  form.value = { id: null, amount: '', category: '', description: '', date: '' }
}

const clearFilters = () => {
  filters.value = { category: '', date: '' }
  fetchExpenses()
}

const renderChart = () => {
  if (!chartCanvas.value) return
  // Destroy previous chart instance if exists
  if (chartInstance) {
    chartInstance.destroy()
  }
  // Group expenses by category
  const grouped = expenses.value.reduce((acc, exp) => {
    acc[exp.category] = (acc[exp.category] || 0) + parseFloat(exp.amount)
    return acc
  }, {})
  const labels = Object.keys(grouped)
  const data = Object.values(grouped)
  chartInstance = new Chart(chartCanvas.value, {
    type: 'pie', // Change to 'bar' for bar chart
    data: {
      labels,
      datasets: [{
        label: 'Expenses by Category',
        data,
        backgroundColor: [
          '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'
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
})

watch(expenses, () => {
  renderChart()
})
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
form, .filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
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
</style>
