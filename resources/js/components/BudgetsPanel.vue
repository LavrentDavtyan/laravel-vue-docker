<template>
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="m-0">Budgets</h4>
            <input type="month" v-model="month" class="form-control" style="max-width: 180px" @change="load" />
        </div>

        <div class="mb-3 d-flex gap-2">
            <input v-model.trim="form.category" class="form-control" placeholder="Category (e.g., Food)" style="max-width:200px" />
            <input v-model.number="form.amount" type="number" class="form-control" placeholder="Amount" style="max-width:140px" />
            <select v-model="form.currency" class="form-select" style="max-width:110px">
                <option>USD</option><option>EUR</option><option>AMD</option>
            </select>
            <button class="btn btn-primary" @click="save">Save</button>
        </div>

        <table class="table table-sm align-middle">
            <thead>
            <tr>
                <th>Category</th>
                <th class="text-end">Budget</th>
                <th class="text-end">Spend</th>
                <th style="width: 220px;">Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="row in stats" :key="row.category">
                <td>{{ row.category }}</td>
                <td class="text-end">{{ money(row.budget, row.currency) }}</td>
                <td class="text-end">{{ money(row.spend, row.currency) }}</td>
                <td>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar"
                             :class="barClass(row.status)"
                             role="progressbar"
                             :style="{width: Math.min(100, row.pct) + '%'}"></div>
                    </div>
                    <small :class="textClass(row.status)">{{ row.status }} ({{ row.pct }}%)</small>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" @click="remove(row.category)">Delete</button>
                </td>
            </tr>
            <tr v-if="!stats.length">
                <td colspan="5" class="text-muted text-center">No budgets yet for this month.</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { watch } from 'vue'
import axios from '../http'



const month = ref(new Date().toISOString().slice(0,7)) // YYYY-MM
const stats = ref([])

const form = ref({ category: '', amount: '', currency: 'USD' })

function monthAnchor() { return month.value + '-01' }

const money = (n, cur='USD') => {
    try { return new Intl.NumberFormat(undefined,{style:'currency',currency:cur}).format(Number(n)||0) }
    catch { return (Number(n)||0).toFixed(2) }
}

const barClass = (status) => ({
    'bg-success': status === 'Under',
    'bg-warning': status === 'Near',
    'bg-danger':  status === 'Over'
})
const textClass = (status) => ({
    'text-success': status === 'Under',
    'text-warning': status === 'Near',
    'text-danger':  status === 'Over'
})

async function load() {
    const res = await axios.get('/budgets/stats', { params: { month: monthAnchor() } })
    stats.value = res.data
}
async function save() {
    if (!form.value.category || !form.value.amount) return alert('Fill category and amount')
    await axios.post('/budgets', {
        category: form.value.category,
        month: monthAnchor(),
        amount_decimal: String(form.value.amount).replace(',', '.'),
        currency: form.value.currency
    })
    form.value.amount = ''
    await load()
}
async function remove(category) {
    // fetch budget id to delete
    const list = await axios.get('/budgets', { params: { month: monthAnchor() } })
    const match = list.data.find(b => b.category === category)
    if (!match) return
    if (!confirm(`Delete budget for ${category}?`)) return
    await axios.delete(`/budgets/${match.id}`)
    await load()
}

const route = useRoute()

onMounted(() => {
    const f = route.query?.date_from
    if (f && /^\d{4}-\d{2}-\d{2}$/.test(f)) {
        month.value = f.slice(0,7) // YYYY-MM
        load()
    }
})

watch(
  () => route.fullPath,
  () => {
    load()
  },
  { immediate: true }
)
</script>
