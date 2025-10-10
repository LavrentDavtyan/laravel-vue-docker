<!-- resources/js/share/ShareTopic.vue -->
<template>
    <div class="page-wrap p-3 p-md-4">
        <!-- If access denied, show join flow instead of the full topic UI -->
        <div v-if="accessDenied" class="mx-auto" style="max-width: 720px;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="mb-0">Request Access</h2>
                <RouterLink class="btn btn-link" :to="{ name: 'share.list' }">← Back to Topics</RouterLink>
            </div>

            <div class="alert alert-warning shadow-sm">
                You’re not a member of <strong>Topic #{{ id ?? '—' }}</strong>. Ask the owner to approve your request.
            </div>

            <!-- Join request panel -->
            <JoinRequestPanel :topic-id="id" @requested="onRequested" />

            <!-- After request -->
            <div v-if="requestToast" class="alert alert-info mt-3 shadow-sm">
                {{ requestToast }}
            </div>
        </div>

        <!-- Normal topic UI -->
        <template v-else>
            <!-- Hero / Header -->
            <div class="hero card card-body border-0 shadow-sm mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="hero-badge">{{ topicCurrency || 'USD' }}</div>
                        <h2 class="mb-0 fw-semibold">Topic #{{ id }}</h2>
                        <span v-if="isClosed" class="badge rounded-pill bg-danger">Closed</span>
                        <span v-else class="badge rounded-pill bg-success">Open</span>
                        <span v-if="isOwner" class="badge rounded-pill bg-primary-subtle text-primary">Owner</span>
                    </div>
                    <RouterLink class="btn btn-link text-decoration-none" :to="{ name: 'share.list' }">
                        ← Back to Topics
                    </RouterLink>
                </div>

                <!-- Quick stats -->
                <div class="row g-3 mt-3">
                    <div class="col-6 col-lg-3">
                        <div class="mini-card card h-100 border-0 shadow-xxs">
                            <div class="card-body py-3">
                                <div class="text-muted small">Members</div>
                                <div class="h4 mb-0">{{ members.length || 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="mini-card card h-100 border-0 shadow-xxs">
                            <div class="card-body py-3">
                                <div class="text-muted small">Expenses</div>
                                <div class="h4 mb-0">{{ expenses.length || 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="mini-card card h-100 border-0 shadow-xxs">
                            <div class="card-body py-3">
                                <div class="text-muted small">Filtered Total</div>
                                <div class="h4 mb-0">{{ formatMoney(filteredTotal) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="mini-card card h-100 border-0 shadow-xxs">
                            <div class="card-body py-3">
                                <div class="text-muted small">Top Payer</div>
                                <div class="h5 mb-0 text-truncate">{{ topPayerName }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs sticky-tabs bg-body mb-3">
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: tab === 'overview' }" @click="tab = 'overview'">Overview</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: tab === 'expenses' }" @click="openExpenses">Expenses</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: tab === 'members' }" @click="openMembers">
                        Members
                        <span v-if="isOwner && pendingCount > 0" class="badge rounded-pill bg-warning text-dark ms-2">
              {{ pendingCount }}
            </span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: tab === 'balances' }" @click="openBalances">Balances</button>
                </li>
            </ul>

            <!-- OVERVIEW TAB -->
            <div v-if="tab === 'overview'" class="card card-body border-0 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar bg-primary-subtle text-primary">ℹ️</div>
                    <div>
                        <h5 class="mb-1">Welcome to Topic #{{ id }}</h5>
                        <div class="text-muted">
                            Use the tabs above to add expenses, manage members, and view balances & suggested transfers.
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXPENSES TAB -->
            <div v-else-if="tab === 'expenses'">
                <!-- Quick Add Bar -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6 col-lg-5">
                                <label class="form-label mb-1">Description</label>
                                <input v-model="desc" class="form-control form-control-lg" :disabled="isClosed" placeholder="e.g. Lunch, Tickets…" />
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label mb-1">Amount</label>
                                <input
                                    v-model.number="amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-lg"
                                    :disabled="isClosed"
                                    placeholder="0.00"
                                />
                            </div>
                            <div class="col-12 col-md-3 col-lg-3">
                                <div class="d-flex gap-2 quick-buttons">
                                    <button class="btn btn-outline-secondary" @click="bump(5)"  :disabled="isClosed">+5</button>
                                    <button class="btn btn-outline-secondary" @click="bump(10)" :disabled="isClosed">+10</button>
                                    <button class="btn btn-outline-secondary" @click="bump(20)" :disabled="isClosed">+20</button>
                                    <button class="btn btn-outline-secondary" @click="amount = 100" :disabled="isClosed">+100</button>
                                </div>
                            </div>
                            <div class="col-12 col-lg-2">
                                <button class="btn btn-success btn-lg w-100" :disabled="creating || !canCreate || isClosed" @click="addExpense">
                                    {{ creating ? 'Saving…' : 'Add Expense' }}
                                </button>
                            </div>
                        </div>

                        <!-- keep v-if / v-else-if adjacent -->
                        <template v-if="isClosed">
                            <small class="text-muted d-block mt-2">Topic is closed. You can’t add expenses.</small>
                        </template>
                        <template v-else-if="!canCreate">
                            <small class="text-muted d-block mt-2">Enter a description and positive amount.</small>
                        </template>
                    </div>
                </div>

                <!-- Filters / Sorting -->
                <div class="card card-body border-0 shadow-sm mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Member</label>
                            <select v-model="filters.memberId" class="form-select">
                                <option :value="null">All members</option>
                                <option v-for="m in members" :key="m.id" :value="m.id">{{ m.display_name }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">From</label>
                            <input v-model="filters.from" type="date" class="form-control" />
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">To</label>
                            <input v-model="filters.to" type="date" class="form-control" />
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Search</label>
                            <input v-model="filters.q" class="form-control" placeholder="Find description…" />
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Sort</label>
                            <select v-model="filters.sort" class="form-select">
                                <option value="date_desc">Date ↓</option>
                                <option value="date_asc">Date ↑</option>
                                <option value="amount_desc">Amount ↓</option>
                                <option value="amount_asc">Amount ↑</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">Reset filters</button>
                    </div>
                </div>

                <!-- Summary + Charts -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Total (filtered)</div>
                                <div class="display-6 mb-0">{{ formatMoney(filteredTotal) }}</div>
                                <div class="text-muted mt-2">Items: <strong>{{ filteredExpenses.length }}</strong></div>
                                <div class="text-muted">Top payer: <strong>{{ topPayerName }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Spending by Payer</h6>
                                <div class="chart-wrap">
                                    <canvas ref="payerChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Spending Over Time</h6>
                                <div class="chart-wrap">
                                    <canvas ref="timeChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List (filtered + sorted) -->
                <div v-if="error" class="alert alert-danger shadow-sm">{{ error }}</div>

                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th>Payer</th>
                                <th class="text-end">Amount</th>
                                <th style="min-width:160px;">Date</th>
                                <th class="text-end" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="e in sortedExpenses" :key="e.id">
                                <td class="fw-medium">{{ e.description }}</td>
                                <td><span class="badge bg-body-tertiary text-dark border">{{ userNameById(e.payer_user_id) }}</span></td>
                                <td class="text-end fw-semibold">{{ formatMoney(e.amount) }}</td>
                                <td class="text-muted">{{ formatDate(e.created_at) }}</td>
                                <td class="text-end">
                                    <button
                                        class="btn btn-outline-danger btn-sm"
                                        v-if="isOwner || e.payer_user_id === meId"
                                        @click="onDelete(e.id)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!sortedExpenses.length && !loading">
                                <td colspan="5" class="text-muted text-center py-4">
                                    No expenses match your filters.
                                </td>
                            </tr>
                            <tr v-if="loading">
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Loading…
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MEMBERS TAB (owner controls + join requests) -->
            <div v-else-if="tab === 'members'">
                <div class="card card-body border-0 shadow-sm mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge rounded-pill" :class="isClosed ? 'bg-danger' : 'bg-success'">
              {{ isClosed ? 'Closed' : 'Open' }}
            </span>
                        <button v-if="isOwner && !isClosed" class="btn btn-outline-warning" @click="onCloseTopic" :disabled="loading">
                            Close Topic
                        </button>
                        <button v-if="isOwner && isClosed" class="btn btn-success" @click="onOpenTopic" :disabled="loading">
                            Reopen Topic
                        </button>
                    </div>
                </div>

                <div class="card card-body border-0 shadow-sm mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div v-if="isOwner" class="input-group" style="max-width: 620px;">
                            <span class="input-group-text">Invite Link</span>
                            <input class="form-control" :value="inviteUrl" readonly @focus="$event.target.select()" />
                            <button class="btn btn-outline-secondary" @click="rotate" :disabled="loading">Rotate</button>
                        </div>
                        <div v-else class="text-muted">Ask the owner for invite link.</div>
                    </div>
                </div>

                <div v-if="error" class="alert alert-danger shadow-sm">{{ error }}</div>

                <!-- Owner-only pending join requests -->
                <div v-if="isOwner" class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="mb-0">Join Requests</h5>
                            <button class="btn btn-sm btn-outline-primary" :disabled="loading" @click="refreshRequests">
                                Refresh
                            </button>
                        </div>
                        <div v-if="!pendingRequests.length" class="text-muted">No pending requests.</div>

                        <div v-else class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Requester</th>
                                    <th>Email</th>
                                    <th>Message</th>
                                    <th>Requested</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="r in pendingRequests" :key="r.id">
                                    <td>{{ r.requester?.name || ('User #' + r.requester_user_id) }}</td>
                                    <td>{{ r.requester?.email || '—' }}</td>
                                    <td class="text-break">{{ r.message || '—' }}</td>
                                    <td class="text-muted">{{ formatDate(r.created_at) }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-success btn-sm me-2" @click="approve(r.id)" :disabled="loading">Approve</button>
                                        <button class="btn btn-outline-danger btn-sm" @click="deny(r.id)" :disabled="loading">Deny</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- Members table -->
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr><th>Name</th><th>Role</th><th>Joined</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                            <tr v-for="m in members" :key="m.id">
                                <td class="fw-medium">{{ m.display_name }}</td>
                                <td>
                                    <span class="badge bg-body-tertiary text-dark border text-uppercase">{{ m.role }}</span>
                                </td>
                                <td class="text-muted">{{ formatDate(m.joined_at) }}</td>
                                <td class="text-end">
                                    <button v-if="!isOwner && m.user_id === meId" class="btn btn-outline-danger btn-sm" @click="leave">
                                        Leave
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!members.length && !loading">
                                <td colspan="4" class="text-muted text-center py-4">No members yet.</td>
                            </tr>
                            <tr v-if="loading">
                                <td colspan="4" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Loading…
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- BALANCES TAB -->
            <div v-else>
                <div v-if="error" class="alert alert-danger shadow-sm">{{ error }}</div>

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Member Balances</h5>
                                <div class="table-responsive mt-3">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light"><tr><th>Member</th><th class="text-end">Net</th></tr></thead>
                                        <tbody>
                                        <tr v-for="b in balances" :key="b.member_id">
                                            <td class="fw-medium">{{ memberNameById(b.member_id) }}</td>
                                            <td class="text-end">
                                                <span :class="{ 'text-success': b.net > 0, 'text-danger': b.net < 0 }">{{ formatMoney(b.net) }}</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!balances.length && !loading">
                                            <td colspan="2" class="text-muted text-center py-4">No balances yet.</td>
                                        </tr>
                                        <tr v-if="loading">
                                            <td colspan="2" class="text-center py-4">
                                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                                Loading…
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Suggested Transfers</h5>
                                <ul class="list-group list-group-flush mt-3">
                                    <li
                                        v-for="t in transfers"
                                        :key="`${t.from_member_id}-${t.to_member_id}-${t.amount}`"
                                        class="list-group-item d-flex justify-content-between align-items-center"
                                    >
                                        <div>
                                            <strong>{{ memberNameById(t.from_member_id) }}</strong>
                                            <span class="mx-1">→</span>
                                            <strong>{{ memberNameById(t.to_member_id) }}</strong>
                                        </div>
                                        <div class="badge bg-primary-subtle text-primary border">{{ formatMoney(t.amount) }}</div>
                                    </li>
                                    <li v-if="!transfers.length && !loading" class="list-group-item text-muted text-center">
                                        Everything is settled 🎉
                                    </li>
                                    <li v-if="loading" class="list-group-item text-center">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        Loading…
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Toast -->
            <div
                v-if="showToast"
                class="alert"
                :class="toastType === 'success' ? 'alert-success' : 'alert-danger'"
                style="position: fixed; bottom: 20px; right: 20px; z-index: 1050; min-width: 250px;"
            >
                {{ toastMsg }}
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useShareStore } from '@/stores/share';
import JoinRequestPanel from '@/share/JoinRequestPanel.vue';
import Chart from 'chart.js/auto';

const route = useRoute();
const share = useShareStore();

const {
    members,
    expenses,
    inviteToken,
    isOwner,
    balances,
    transfers,
    loading,
    error,
    topicStatus,
    joinRequests,
    topicCurrency,
} = storeToRefs(share);

const tab = ref('overview');
const accessDenied = ref(false);
const requestToast = ref('');

/* ---------- ID & meta ---------- */
const id = computed(() => {
    const raw = route.params.id ?? route.params.topicId ?? route.params.topic ?? null;
    const n = parseInt(raw, 10);
    return Number.isFinite(n) ? n : null;
});
const topicTitle = computed(() => (id.value ? `Topic #${id.value}` : 'Topic'));
const inviteUrl  = computed(() => (inviteToken.value ? `${window.location.origin}/share/join/${inviteToken.value}` : ''));
const isClosed   = computed(() => topicStatus.value === 'closed');

const me = JSON.parse(localStorage.getItem('user') || '{}');
const meId = me?.id ?? null;

/* ---------- Toast ---------- */
const toastMsg = ref('');
const toastType = ref('success');
const showToast = ref(false);
function showToastMsg(message, type = 'success') {
    toastMsg.value = message;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 2500);
}

/* ---------- Filters & sorting ---------- */
const filters = ref({
    memberId: null,
    from: null,
    to: null,
    q: '',
    sort: 'date_desc',
});
function resetFilters() {
    filters.value = { memberId: null, from: null, to: null, q: '', sort: 'date_desc' };
}

/* ---------- Derived lists ---------- */
const filteredExpenses = computed(() => {
    const list = (expenses.value || []).filter((e) => {
        if (filters.value.memberId) {
            const m = members.value.find((mm) => mm.id === filters.value.memberId);
            if (!m || e.payer_user_id !== m.user_id) return false;
        }
        if (filters.value.from) {
            const d = (e.date ?? e.created_at?.split('T')[0]) || '';
            if (d && d < filters.value.from) return false;
        }
        if (filters.value.to) {
            const d = (e.date ?? e.created_at?.split('T')[0]) || '';
            if (d && d > filters.value.to) return false;
        }
        if (filters.value.q) {
            const q = filters.value.q.toLowerCase();
            if (!(e.description || '').toLowerCase().includes(q)) return false;
        }
        return true;
    });
    return list;
});

const sortedExpenses = computed(() => {
    const list = [...filteredExpenses.value];
    switch (filters.value.sort) {
        case 'date_asc':
            return list.sort((a, b) => (a.created_at || '').localeCompare(b.created_at || ''));
        case 'amount_desc':
            return list.sort((a, b) => Number(b.amount || 0) - Number(a.amount || 0));
        case 'amount_asc':
            return list.sort((a, b) => Number(a.amount || 0) - Number(b.amount || 0));
        case 'date_desc':
        default:
            return list.sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''));
    }
});

/* ---------- Summary ---------- */
const filteredTotal = computed(() =>
    filteredExpenses.value.reduce((sum, e) => sum + Number(e.amount || 0), 0)
);

const payerRows = computed(() => {
    const byUser = {};
    for (const e of filteredExpenses.value) {
        const uid = e.payer_user_id;
        byUser[uid] = (byUser[uid] || 0) + Number(e.amount || 0);
    }
    return Object.entries(byUser).map(([uid, total]) => ({
        name: userNameById(Number(uid)),
        total: Number(total),
    }));
});

const topPayerName = computed(() => {
    if (!payerRows.value.length) return '—';
    const best = payerRows.value.reduce((a, b) => (a.total >= b.total ? a : b));
    return `${best.name} (${formatMoney(best.total)})`;
});

/* ---------- Charts ---------- */
const payerChart = ref(null);
const timeChart  = ref(null);
let payerChartInstance = null;
let timeChartInstance  = null;

watch(filteredExpenses, () => {
    renderPayerChart();
    renderTimeChart();
});

function renderPayerChart() {
    if (!payerChart.value) return;
    const ctx = payerChart.value.getContext('2d');
    if (payerChartInstance) payerChartInstance.destroy();

    const labels = payerRows.value.map(r => r.name);
    const data = payerRows.value.map(r => r.total);

    payerChartInstance = new Chart(ctx, {
        type: 'pie',
        data: { labels, datasets: [{ data, backgroundColor: ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949'] }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
}

function renderTimeChart() {
    if (!timeChart.value) return;
    const ctx = timeChart.value.getContext('2d');
    if (timeChartInstance) timeChartInstance.destroy();

    const map = {};
    for (const e of filteredExpenses.value) {
        const d = e.date ?? e.created_at?.split('T')[0];
        if (!d) continue;
        map[d] = (map[d] || 0) + Number(e.amount || 0);
    }
    const labels = Object.keys(map).sort();
    const data = labels.map(d => map[d]);

    timeChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: `Spending (${topicCurrency.value || 'USD'})`, data, backgroundColor: '#4e79a7' }] },
        options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
}

/* ---------- Helpers ---------- */
function formatDate(ts) {
    if (!ts) return '';
    const d = new Date(ts);
    return isNaN(d.getTime()) ? '' : d.toLocaleString();
}
function formatMoney(n) {
    const num = Number(n ?? 0);
    return num.toFixed(2);
}
function memberNameById(memberId) {
    const m = members.value.find(m => m.id === memberId);
    return m?.display_name ?? `Member #${memberId}`;
}
function userNameById(userId) {
    const m = members.value.find(m => m.user_id === userId);
    return m?.display_name ?? `User #${userId}`;
}

/* ---------- Owner-only join requests ---------- */
const pendingRequests = computed(() => (joinRequests.value || []).filter(r => r.status === 'pending'));
const pendingCount    = computed(() => pendingRequests.value.length);

async function refreshRequests() {
    if (!id.value || !isOwner.value) return;
    await share.fetchJoinRequests(id.value);
}
async function approve(reqId) {
    if (!id.value) return;
    await share.approveJoinRequest(id.value, reqId);
    showToastMsg('Request approved.');
}
async function deny(reqId) {
    if (!id.value) return;
    await share.denyJoinRequest(id.value, reqId);
    showToastMsg('Request denied.');
}

/* ---------- Actions ---------- */
async function rotate() {
    if (!id.value) return;
    await share.rotateInvite(id.value);
    await share.fetchMembers(id.value);
}
async function leave() {
    if (!id.value) return;
    await share.leaveTopic(id.value);
    history.back();
}
async function onDelete(expenseId) {
    if (!id.value) return;
    if (!confirm('Delete this expense? This cannot be undone.')) return;
    await share.deleteExpense(id.value, expenseId);
}
async function onCloseTopic() {
    if (!id.value) return;
    if (!confirm('Close this topic? No more new expenses will be allowed.')) return;
    try {
        await share.closeTopic(id.value);
        showToastMsg('Topic closed successfully.');
    } catch (e) {
        showToastMsg(e?.response?.data?.message || e.message, 'danger');
    }
}
async function onOpenTopic() {
    if (!id.value) return;
    try {
        await share.reopenTopic(id.value);
        showToastMsg('Topic reopened successfully.');
    } catch (e) {
        showToastMsg(e?.response?.data?.message || e.message, 'danger');
    }
}

/* ---------- Add expense ---------- */
const desc = ref('');
const amount = ref(null);
const creating = ref(false);
const canCreate = computed(() => !!desc.value && Number(amount.value) > 0);
function bump(n) {
    amount.value = Number(amount.value || 0) + Number(n);
}
async function addExpense() {
    if (!id.value || !canCreate.value || isClosed.value) return;
    creating.value = true;
    try {
        await share.createExpense(id.value, { description: desc.value, amount: amount.value });
        await share.fetchExpenses(id.value);
        desc.value = '';
        amount.value = null;
        renderPayerChart();
        renderTimeChart();
    } finally {
        creating.value = false;
    }
}

/* ---------- Tab loaders ---------- */
async function openMembers() {
    tab.value = 'members';
    await guardedFetchMembers();
    if (!accessDenied.value && isOwner.value) {
        await share.fetchJoinRequests(id.value);
    }
}
async function openExpenses() {
    tab.value = 'expenses';
    await guardedFetchMembers();
    if (accessDenied.value) return;
    await share.fetchExpenses(id.value);
    renderPayerChart();
    renderTimeChart();
}
async function openBalances() {
    tab.value = 'balances';
    await guardedFetchMembers();
    if (accessDenied.value) return;
    await share.fetchBalances(id.value);
}

/**
 * Call store.fetchMembers and detect 403 → set `accessDenied`.
 */
async function guardedFetchMembers() {
    if (!id.value) { share.error = 'Topic id is missing from the URL.'; return; }
    accessDenied.value = false;
    await share.fetchMembers(id.value);
    if (share.error) {
        const msg = String(share.error).toLowerCase();
        if (msg.includes('not part of this topic') || msg.includes('403') || msg.includes('unauthorized') || msg.includes('forbidden')) {
            accessDenied.value = true;
        }
    }
}

/* ---------- Initial load ---------- */
onMounted(async () => {
    if (!id.value) return;
    await guardedFetchMembers();
    if (!accessDenied.value && isOwner.value) {
        await share.fetchJoinRequests(id.value);
    }
});
</script>

<style scoped>
.page-wrap {
    max-width: 1320px;
    margin-inline: auto;
}

/* Tiny, soft shadow for stat cards */
.shadow-xxs { box-shadow: 0 .25rem .5rem rgba(0,0,0,.04) !important; }

/* Hero header */
.hero {
    background: linear-gradient(180deg, var(--bs-body-bg) 0%, #f8f9fb 100%);
    border-radius: 1rem;
}
.hero-badge {
    background: var(--bs-primary);
    color: #fff;
    border-radius: .75rem;
    padding: .25rem .5rem;
    font-weight: 600;
    letter-spacing: .5px;
    font-size: .85rem;
}

/* Tabs that stick under the hero on scroll */
.sticky-tabs {
    position: sticky;
    top: 0; /* if you have a navbar, adjust */
    z-index: 100;
    padding: .25rem .5rem;
    border-radius: .75rem;
    border: 1px solid var(--bs-border-color);
}

/* Chart wrapper keeps canvases neat */
.chart-wrap {
    min-height: 220px;
    display: grid;
    place-items: center;
}

/* Avatars for overview info rows */
.avatar {
    width: 40px; height: 40px;
    border-radius: .75rem;
    display: grid; place-items: center;
    font-size: 1.1rem;
}

/* Quick buttons compress nicely on small screens */
.quick-buttons .btn {
    height: 46px;
    min-width: 64px;
}

/* Table row subtle hover */
.table-hover tbody tr:hover {
    background: #fbfbfd;
}
</style>
