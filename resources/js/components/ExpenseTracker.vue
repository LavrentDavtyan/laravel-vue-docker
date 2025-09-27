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
import axios from '../http'            // keep axios wrapper with token + baseURL
import Chart from 'chart.js/auto'

const expenses = ref([])
const filters = ref({ category: '', date: '' })
const chartCanvas = ref(null)
let chartInstance = null

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
}

onMounted(() => {
    fetchExpenses()
})

watch(expenses, renderChart)
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
