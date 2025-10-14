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

        <!-- Filters -->
        <div class="filters pt-5 d-flex align-items-end gap-2 flex-wrap">
            <input v-model="filters.category" type="text" class="form-control" placeholder="Filter by category" style="max-width: 200px;" />
            <input v-model="filters.date_from" type="date" class="form-control" placeholder="From" style="max-width: 180px;" />
            <input v-model="filters.date_to" type="date" class="form-control" placeholder="To" style="max-width: 180px;" />
            <button @click="applyFilters" class="btn btn-success">Apply Filters</button>
            <button @click="clearFilters" class="btn btn-secondary">Clear</button>
        </div>

        <!-- Presets -->
        <div class="presets-row d-flex gap-2 mt-3 flex-wrap">
            <button @click="setPreset('today')"  :class="['btn','btn-sm', activePreset==='today'  ? 'btn-primary' : 'btn-light']">Today</button>
            <button @click="setPreset('week')"   :class="['btn','btn-sm', activePreset==='week'   ? 'btn-primary' : 'btn-light']">This Week</button>
            <button @click="setPreset('month')"  :class="['btn','btn-sm', activePreset==='month'  ? 'btn-primary' : 'btn-light']">This Month</button>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-6 col-12">
                <div class="chart-section">
                    <h3>Expenses by Category</h3>
                    <canvas ref="chartCanvas" width="400" height="300"></canvas>
                </div>
            </div>
            <div class="col-lg-6 col-12">
                <div class="chart-section">
                    <h3>Expenses per Day</h3>
                    <canvas ref="lineChartCanvas" width="650" height="300"></canvas>
                </div>
            </div>
        </div>



        <!-- Table -->
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
                      <router-link :to="{ path: `/expenses/category/${expense.category}`, query: route.query }">
                        {{ expense.category }}
                      </router-link>
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

            <!-- Budgets panel (month only) -->
            <div v-if="activePreset === 'month'" class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="mb-0">Budgets — {{ prettyMonth }}</h3>
                    <button class="btn btn-outline-secondary btn-sm" @click="showBudgetPanel = !showBudgetPanel">
                        {{ showBudgetPanel ? 'Hide budgets' : 'Show budgets' }}
                    </button>
                </div>

                <div v-show="showBudgetPanel" class="card p-3">
                    <!-- Add/Edit form -->
                    <form class="row g-2 mb-3" @submit.prevent="saveBudget">
                        <div class="col-md-3">
                            <input v-model="form.category" type="text" class="form-control" placeholder="Category (e.g., fuel)" required>
                        </div>
                        <div class="col-md-3">
                            <input v-model.number="form.amount_decimal" type="number" step="0.01" min="0" class="form-control" placeholder="Monthly budget" required>
                        </div>
                        <div class="col-md-2">
                            <input v-model="form.currency" type="text" class="form-control" placeholder="Currency" maxlength="3">
                        </div>
                        <div class="col-md-2 d-flex align-items-center">
                            <input type="month"
                                   v-model="monthControl"
                                   @change="onMonthChange"
                                   class="form-control form-control-sm"
                                   style="min-width: 150px;">
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-primary w-100">
                                {{ form.id ? 'Update' : 'Add' }}
                            </button>
                        </div>
                    </form>

                    <!-- Budgets table -->
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Budget</th>
                                <th class="text-end">Spent</th>
                                <th class="text-end">Remaining</th>
                                <th>Status</th>
                                <th style="width:260px;">Progress</th>
                                <th class="text-end">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="row in budgetRows" :key="row.id">
                                <td>{{ row.category }}</td>
                                <td class="text-end">{{ money(row.amount_decimal, row.currency) }}</td>
                                <td class="text-end">{{ money(row.spent, row.currency) }}</td>
                                <td class="text-end" :class="{'text-danger': row.remaining < 0}">
                                    {{ money(row.remaining, row.currency) }}
                                </td>
                                <td>
                                    <span v-if="row.status !== '—'" class="badge" :class="badgeClass(row.status)">{{ row.status }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>
                                    <div class="progress budget-progress">
                                        <div class="progress-bar"
                                             :class="barClass(row.status)"
                                             :style="{ width: Math.min(100, row.pct) + '%' }" />
                                    </div>
                                    <div v-if="row.overflowPct > 0" class="progress budget-overflow mt-1">
                                        <div class="progress-bar bg-overflow" :style="{ width: Math.min(100, row.overflowPct) + '%' }" />
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-secondary me-2" @click="startEdit(row)">Edit</button>
                                    <button class="btn btn-sm btn-danger" @click="removeBudget(row.id)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!budgetRows.length">
                                <td colspan="7" class="text-center text-muted py-3">
                                    No budgets for {{ currentMonthAnchor() }}. Add one above.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



            <div class="mt-3">
                <ExportExcel type="expenses" />
            </div>
        </div>

        <ConfirmModal
            ref="confirmRef"
            :title="confirmTitle"
            :message="confirmMessage"
            @confirm="onConfirm"
        />
        <MessageModal ref="msgRef" :title="msgTitle" :message="msgMessage" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useRoute, useRouter, onBeforeRouteUpdate } from 'vue-router'
import axios from '../http'
import Chart from 'chart.js/auto'
import ExportExcel from './ExportExcel.vue'
import ConfirmModal from './common/ConfirmModal.vue'
import MessageModal from './common/MessageModal.vue'

// Format a Date as YYYY-MM-DD in local time (no UTC shift)
function fmtLocalYMD(d) {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}
function endOfMonth(d) {
    // last day of this month in local time
    const e = new Date(d.getFullYear(), d.getMonth() + 1, 0)
    return fmtLocalYMD(e)
}
const route = useRoute()
const router = useRouter()

const filters = ref({
    category: route.query.category ? String(route.query.category) : '',
    date_from: route.query.date_from ? String(route.query.date_from) : '',
    date_to:   route.query.date_to   ? String(route.query.date_to)   : ''
})

if (!route.query.date_from && !route.query.date_to) {
  const today = new Date()
  const start = new Date(today.getFullYear(), today.getMonth(), 1)
  filters.value.date_from = fmtLocalYMD(start)
  filters.value.date_to   = endOfMonth(today)
  router.replace({ query: { ...route.query, date_from: filters.value.date_from, date_to: filters.value.date_to } })
}
const activePreset = ref('month')

// Month control for budgets table (YYYY-MM)
const monthControl = ref((() => {
  const base = filters.value.date_to || filters.value.date_from || new Date().toISOString().slice(0,10)
  const d = new Date(base)
  const ym = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`
  return ym
})())

const prettyMonth = computed(() => {
  const d = new Date(`${monthControl.value}-01`)
  return d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
})

function onMonthChange() {
  // when the month changes, reload budgets & stats and clear editing form
  form.value = { id: null, category: '', amount_decimal: null, currency: form.value.currency || 'USD' }
  Promise.all([loadBudgets(), loadBudgetStats()])
}

// Show budget UI only for "This Month" preset
const showBudgets = computed(() => activePreset.value === 'month')

// Portion above 100% budget (overflow)
function overflowPct(entry) {
  const p = Number(entry?.pct || 0)
  return Math.max(0, p - 100)
}

const expenses = ref([])
const chartCanvas = ref(null)
let chartInstance = null
const lineChartCanvas = ref(null)
let lineChartInstance = null
let refreshInFlight = false


const confirmRef = ref(null)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmCallback = ref(null)

const msgRef = ref(null)
const msgTitle = ref('')
const msgMessage = ref('')

function showMessage(title, message) {
    msgTitle.value = title
    msgMessage.value = message
    msgRef.value?.show()
}

function askConfirm(title, message, cb) {
    confirmTitle.value = title
    confirmMessage.value = message
    confirmCallback.value = cb
    confirmRef.value?.show()
}

async function onConfirm() {
    try {
        if (typeof confirmCallback.value === 'function') {
            await confirmCallback.value()
        }
    } finally {
        confirmCallback.value = null
    }
}

// Helper to refresh everything (expenses + budgets + stats), guarded to prevent parallel refreshes
async function refreshAll() {
  if (refreshInFlight) return
  refreshInFlight = true
  try {
    await fetchExpenses()
    await Promise.all([loadBudgetStats(), loadBudgets()])
  } finally {
    refreshInFlight = false
  }
}

// Window focus/visibility hooks so navigating back or refocusing refreshes data
function handleFocus() {
  refreshAll()
}
function handleVisibility() {
  if (document.visibilityState === 'visible') {
    refreshAll()
  }
}

function toISODate(d) { return fmtLocalYMD(d) }

function setPreset(type) {
    const today = new Date()
    let from = '', to = ''
    switch (type) {
        case 'today': from = to = toISODate(today); break
        case 'week': {
            const start = new Date(today)
            const weekday = today.getDay()
            start.setDate(today.getDate() - weekday)
            from = toISODate(start); to = toISODate(today); break
        }
        case 'month': {
            const start = new Date(today.getFullYear(), today.getMonth(), 1)
            from = toISODate(start); to = endOfMonth(today); break
        }
    }
    filters.value.date_from = from
    filters.value.date_to   = to
    activePreset.value = type
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

// --- Budgets badge data ---
const budgetStats = ref([])
const budgetMap = computed(() => {
    const m = {}
    budgetStats.value.forEach(s => { m[s.category] = s })
    return m
})
const barClass = (status) => ({
    'bg-success': status === 'Under',
    'bg-warning': status === 'Near',
    'bg-danger':  status === 'Over'
})
const badgeClass = (status) => ({
    'text-bg-success': status === 'Under',
    'text-bg-warning': status === 'Near',
    'text-bg-danger':  status === 'Over'
})
function currentMonthAnchor() {
  // Prefer explicit month chosen in the month control
  if (monthControl.value) return `${monthControl.value}-01`
  const d = filters.value.date_to
    ? new Date(filters.value.date_to)
    : (filters.value.date_from ? new Date(filters.value.date_from) : new Date())
  return fmtLocalYMD(new Date(d.getFullYear(), d.getMonth(), 1))
}
async function loadBudgetStats() {
    try {
        const res = await axios.get('/budgets/stats', { params: { month: currentMonthAnchor() }})
        budgetStats.value = res.data
    } catch (e) {
        budgetStats.value = []
        console.warn('budgets/stats unavailable', e?.response?.status)
    }
}

// --- Budgets panel state/data ---
const showBudgetPanel = ref(true)

// Raw budgets for the current month
const budgets = ref([])

// Add/Edit form
const form = ref({
  id: null,
  category: '',
  amount_decimal: null,
  currency: 'USD',
})

// Money helper
function money(n, cur = 'USD') {
  const v = Number(n ?? 0)
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: cur || 'USD' }).format(v)
  } catch {
    return `${v.toFixed(2)} ${cur || ''}`.trim()
  }
}

// Load budgets (optionally filtered by month)
async function loadBudgets() {
  const res = await axios.get('/budgets', { params: { month: currentMonthAnchor() } })
  budgets.value = Array.isArray(res.data) ? res.data : []
}

// Create/Update a budget
async function saveBudget() {
  const payload = {
    category: form.value.category?.trim(),
    month: currentMonthAnchor(),
    amount_decimal: form.value.amount_decimal,
    currency: form.value.currency?.trim() || 'USD',
  }

  if (form.value.id) {
    await axios.put(`/budgets/${form.value.id}`, payload)
  } else {
    await axios.post('/budgets', payload)
  }

  form.value = { id: null, category: '', amount_decimal: null, currency: payload.currency }
  await Promise.all([loadBudgets(), loadBudgetStats()])
}

function startEdit(row) {
  form.value = {
    id: row.id,
    category: row.category,
    amount_decimal: row.amount_decimal,
    currency: row.currency || 'USD',
  }
}

function removeBudget(id) {
    askConfirm(
        'Delete budget',
        'Are you sure you want to delete this budget? This cannot be undone.',
        async () => {
            try {
                await axios.delete(`/budgets/${id}`)
                await Promise.all([loadBudgets(), loadBudgetStats()])
                console.log('Budget deleted')
            } catch (e) {
                if (e?.response?.status === 401) {
                    console.warn('Session expired. Please log in again.')
                } else {
                    console.error('Delete budget failed.', e)
                }
            }
        }
    )
}

// Merge budgets with stats for UI table rows (robust fallback)
const budgetRows = computed(() => {
  const monthAnchor = currentMonthAnchor()
  const anchorDate = new Date(monthAnchor)
  const yyyy = anchorDate.getFullYear()
  const mm   = anchorDate.getMonth()

  // Build a quick client-side sum by category for the current month as a fallback
  const clientSums = expenses.value.reduce((acc, e) => {
    if (!e?.category || !e?.date) return acc
    const d = new Date(String(e.date))
    if (d.getFullYear() === yyyy && d.getMonth() === mm) {
      const cat = String(e.category)
      acc[cat] = (acc[cat] || 0) + (Number(e.amount) || 0)
    }
    return acc
  }, {})

  const statsByCat = budgetStats.value.reduce((m, s) => {
    if (s?.category) m[s.category] = s
    return m
  }, {})

  return budgets.value.map(b => {
    const budget = Number(b.amount_decimal || 0)
    const cur    = statsByCat[b.category] || {}

    // Flexible keys from API; fallback to client sums
    let spent = Number(
      cur.spent ?? cur.current ?? cur.total ?? cur.sum ?? clientSums[b.category] ?? 0
    )

    // Percent either from API or computed
      const pct = budget > 0 ? (spent / budget) * 100 : 0
      const status = pct > 100 ? 'Over' : (pct >= 80 ? 'Near' : 'Under')

    return {
      ...b,
      spent,
      pct,
      status,
      remaining: budget - spent,
      overflowPct: Math.max(0, pct - 100),
    }
  })
})

const deleteExpense = (id) => {
    askConfirm(
        'Delete Expense',
        'Are you sure you want to delete this expense? This action cannot be undone.',
        async () => {
            try {
                await axios.delete(`/expenses/${id}`)
                await fetchExpenses()
                await loadBudgetStats()


                showMessage('Deleted', 'The expense was successfully deleted.')
            } catch (e) {
                if (e?.response?.status === 401) {
                    showMessage('Session Expired', 'Please log in again to continue.')
                } else {
                    console.error('Delete failed', e)
                    showMessage('Error', 'Failed to delete the expense. Please try again.')
                }
            }
        }
    )
}

const clearFilters = () => {
    filters.value = { category: '', date_from: '', date_to: '' }
    activePreset.value = ''
    router.replace({ query: { ...route.query, category: undefined, date_from: undefined, date_to: undefined } })
    fetchExpenses()
    loadBudgetStats()
}

const renderChart = () => {
    if (!chartCanvas.value) return
    if (chartInstance) chartInstance.destroy()

    const grouped = expenses.value.reduce((acc, exp) => {
        const key = exp.category ?? 'Uncategorized'
        const val = Number(exp.amount) || 0
        acc[key] = (acc[key] || 0) + val
        return acc
    }, {})
    const labels = Object.keys(grouped)
    const data = Object.values(grouped)

    if (labels.length) {
        chartInstance = new Chart(chartCanvas.value, {
            type: 'pie',
            data: { labels, datasets: [{ label: 'Expenses by Category', data,
                    backgroundColor: ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF'] }] },
            options: { responsive: false, plugins: { legend: { position: 'bottom' } } }
        })
    }

    if (!lineChartCanvas.value) return
    if (lineChartInstance) lineChartInstance.destroy()

    const groupedByDate = expenses.value.reduce((acc, exp) => {
        const key = (exp.date || '').slice(0, 10)
        const val = Number(exp.amount) || 0
        acc[key] = (acc[key] || 0) + val
        return acc
    }, {})
    const sortedDates = Object.keys(groupedByDate).sort()
    const dateTotals = sortedDates.map(d => groupedByDate[d])

    if (sortedDates.length) {
        lineChartInstance = new Chart(lineChartCanvas.value, {
            type: 'line',
            data: {
                labels: sortedDates,
                datasets: [{ label: 'Total Expenses per Day', data: dateTotals, fill: false, borderColor: '#36A2EB', tension: 0.3, pointBackgroundColor: '#36A2EB' }]
            },
            options: {
                responsive: false,
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: { x: { title: { display: true, text: 'Date' } }, y: { title: { display: true, text: 'Amount' }, beginAtZero: true } }
            }
        })
    }
}

onMounted(async () => {
  await refreshAll()
  window.addEventListener('focus', handleFocus)
  document.addEventListener('visibilitychange', handleVisibility)
})

onUnmounted(() => {
  window.removeEventListener('focus', handleFocus)
  document.removeEventListener('visibilitychange', handleVisibility)
})

watch(() => route.query, async () => {
  // start with values from query (if any)
  let category  = route.query.category ? String(route.query.category) : ''
  let date_from = route.query.date_from ? String(route.query.date_from) : ''
  let date_to   = route.query.date_to   ? String(route.query.date_to)   : ''

  // If user navigates to /expenses with no query, default to "This Month"
  if (!date_from && !date_to) {
    const today = new Date()
    const start = new Date(today.getFullYear(), today.getMonth(), 1)
      date_from = fmtLocalYMD(start)
      date_to   = endOfMonth(today)
    activePreset.value = 'month'
    // sync URL (avoid loops by only replacing when values differ)
    const cur = { ...(route.query || {}) }
    if (cur.date_from !== date_from || cur.date_to !== date_to || cur.category !== category) {
      router.replace({ query: { category: category || undefined, date_from, date_to } }).catch(() => {})
    }
  }

  // apply to local filters
  filters.value.category  = category
  filters.value.date_from = date_from
  filters.value.date_to   = date_to

  await fetchExpenses()
  await Promise.all([loadBudgetStats(), loadBudgets()])
})

// Also refresh when the route itself changes (e.g., coming back from /expenses/create)
onBeforeRouteUpdate((to, from, next) => {
  // Let navigation happen; the watch(route.query) will trigger refresh
  next()
})

watch(() => expenses.value, () => renderChart())

// Sync month control with filter month when user changes date range
watch([() => filters.value.date_from, () => filters.value.date_to], () => {
  const base = filters.value.date_to || filters.value.date_from
  if (!base) return
  const d = new Date(base)
  const ym = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`
  if (ym !== monthControl.value) monthControl.value = ym
})
</script>

<style scoped>
.filters {
    display: flex;
    flex-direction: row; /* fixed */
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

.budget-progress { height: 6px; max-width: 180px; }
.budget-overflow { height: 4px; max-width: 180px; }
.bg-overflow { background-color: #b91c1c; }

.card { border: 1px solid #e9ecef; border-radius: 8px; background: #fff; }
</style>
