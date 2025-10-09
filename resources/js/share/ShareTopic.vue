<!-- resources/js/share/ShareTopic.vue -->
<template>
    <div class="p-3 p-md-4">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0">Topic: {{ topicTitle }}</h2>
                <span v-if="isClosed" class="badge bg-danger">Closed</span>
            </div>

            <RouterLink class="btn btn-link" :to="{ name: 'share.list' }">← Back to Topics</RouterLink>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link" :class="{ active: tab === 'overview' }" @click="tab = 'overview'">
                    Overview
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: tab === 'expenses' }" @click="openExpenses">
                    Expenses
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: tab === 'members' }" @click="openMembers">
                    Members
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: tab === 'balances' }" @click="openBalances">
                    Balances
                </button>
            </li>
        </ul>

        <!-- OVERVIEW TAB -->
        <div v-if="tab === 'overview'" class="alert alert-info">
            Placeholder detail page for topic <strong>#{{ id ?? '—' }}</strong>.
        </div>

        <!-- EXPENSES TAB -->
        <div v-else-if="tab === 'expenses'">
            <div class="card card-body mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Description</label>
                        <input v-model="desc" class="form-control" :disabled="isClosed" placeholder="Lunch or Tickets" />
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Amount</label>
                        <input
                            v-model.number="amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            :disabled="isClosed"
                            placeholder="0.00"
                        />
                    </div>
                    <div class="col-12 col-md-auto">
                        <button class="btn btn-success" :disabled="creating || !canCreate || isClosed" @click="addExpense">
                            {{ creating ? 'Saving…' : 'Add Expense' }}
                        </button>
                    </div>
                </div>
                <small class="text-muted mt-2" v-if="isClosed">
                    Topic is closed. You can’t add expenses.
                </small>
                <small class="text-muted mt-2" v-else-if="!canCreate">
                    Enter a description and positive amount.
                </small>
            </div>

            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <table class="table table-sm">
                <thead>
                <tr>
                    <th>Description</th>
                    <th>Payer</th>
                    <th class="text-end">Amount</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="e in expenses" :key="e.id">
                    <td>{{ e.description }}</td>
                    <td>{{ nameOfUser(e.payer_user_id) }}</td>
                    <td class="text-end">{{ money(e.amount) }}</td>
                    <td>{{ when(e.created_at) }}</td>
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
                <tr v-if="!expenses.length && !loading">
                    <td colspan="5" class="text-muted text-center">No expenses yet.</td>
                </tr>
                <tr v-if="loading">
                    <td colspan="5" class="text-center">Loading…</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- MEMBERS TAB -->
        <div v-else-if="tab === 'members'">
            <!-- Only show Close/Reopen here -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="badge" :class="isClosed ? 'bg-danger' : 'bg-success'">
          {{ isClosed ? 'Closed' : 'Open' }}
        </span>

                <button
                    v-if="isOwner && !isClosed"
                    class="btn btn-outline-warning"
                    @click="onCloseTopic"
                    :disabled="loading"
                >
                    Close Topic
                </button>

                <button
                    v-if="isOwner && isClosed"
                    class="btn btn-success"
                    @click="onOpenTopic"
                    :disabled="loading"
                >
                    Reopen Topic
                </button>
            </div>

            <div class="d-flex align-items-center gap-3 mb-2">
                <div v-if="isOwner" class="input-group" style="max-width: 520px;">
                    <span class="input-group-text">Invite Link</span>
                    <input class="form-control" :value="inviteUrl" readonly @focus="$event.target.select()" />
                    <button class="btn btn-outline-secondary" @click="rotate" :disabled="loading">Rotate</button>
                </div>
                <div v-else class="text-muted">Ask the owner for invite link.</div>
            </div>

            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <table class="table table-sm">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="m in members" :key="m.id">
                    <td>{{ m.display_name }}</td>
                    <td>{{ m.role }}</td>
                    <td>{{ when(m.joined_at) }}</td>
                    <td class="text-end">
                        <button v-if="!isOwner && m.user_id === meId" class="btn btn-outline-danger btn-sm" @click="leave">
                            Leave
                        </button>
                    </td>
                </tr>
                <tr v-if="!members.length && !loading">
                    <td colspan="4" class="text-muted text-center">No members yet.</td>
                </tr>
                <tr v-if="loading">
                    <td colspan="4" class="text-center">Loading…</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- BALANCES TAB -->
        <div v-else>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <h5 class="mb-2">Member Balances</h5>
            <table class="table table-sm">
                <thead><tr><th>Member</th><th class="text-end">Net</th></tr></thead>
                <tbody>
                <tr v-for="b in balances" :key="b.member_id">
                    <td>{{ nameOfMember(b.member_id) }}</td>
                    <td class="text-end">
                        <span :class="{ 'text-success': b.net > 0, 'text-danger': b.net < 0 }">{{ money(b.net) }}</span>
                    </td>
                </tr>
                <tr v-if="!balances.length && !loading"><td colspan="2" class="text-muted text-center">No balances yet.</td></tr>
                <tr v-if="loading"><td colspan="2" class="text-center">Loading…</td></tr>
                </tbody>
            </table>

            <h5 class="mt-4 mb-2">Suggested Transfers</h5>
            <ul class="list-group">
                <li
                    v-for="t in transfers"
                    :key="`${t.from_member_id}-${t.to_member_id}-${t.amount}`"
                    class="list-group-item d-flex justify-content-between"
                >
                    <div>
                        <strong>{{ nameOfMember(t.from_member_id) }}</strong>
                        → <strong>{{ nameOfMember(t.to_member_id) }}</strong>
                    </div>
                    <div>{{ money(t.amount) }}</div>
                </li>
                <li v-if="!transfers.length && !loading" class="list-group-item text-muted text-center">
                    Everything is settled 🎉
                </li>
                <li v-if="loading" class="list-group-item text-center">Loading…</li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useShareStore } from '@/stores/share';

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
} = storeToRefs(share);

const tab = ref('overview');

const id = computed(() => {
    const raw = route.params.id ?? route.params.topicId ?? route.params.topic ?? null;
    const n = parseInt(raw, 10);
    return Number.isFinite(n) ? n : null;
});

const topicTitle = computed(() => (id.value ? `Topic #${id.value}` : 'Topic'));
const inviteUrl  = computed(() => inviteToken.value ? `${window.location.origin}/share/join/${inviteToken.value}` : '');
const isClosed   = computed(() => topicStatus.value === 'closed');

const me = JSON.parse(localStorage.getItem('user') || '{}');
const meId = me?.id ?? null;

// Format helpers
function when(ts) {
    if (!ts) return '';
    const d = new Date(ts);
    return isNaN(d.getTime()) ? '' : d.toLocaleString();
}
function money(n) {
    const num = Number(n ?? 0);
    return num.toFixed(2);
}

// Name helpers
function nameOfMember(memberId) {
    const m = members.value.find(m => m.id === memberId);
    return m?.display_name ?? `Member #${memberId}`;
}
function nameOfUser(userId) {
    const m = members.value.find(m => m.user_id === userId);
    return m?.display_name ?? `User #${userId}`;
}

// Tab loaders
async function openMembers() {
    tab.value = 'members';
    if (!id.value) { share.error = 'Topic id is missing from the URL.'; return; }
    await share.fetchMembers(id.value);
}
async function openExpenses() {
    tab.value = 'expenses';
    if (!id.value) { share.error = 'Topic id is missing from the URL.'; return; }
    if (!members.value.length) await share.fetchMembers(id.value);
    await share.fetchExpenses(id.value);
}
async function openBalances() {
    tab.value = 'balances';
    if (!id.value) { share.error = 'Topic id is missing from the URL.'; return; }
    if (!members.value.length) await share.fetchMembers(id.value);
    await share.fetchBalances(id.value);
}

// Actions (now only invoked from Members tab UI)
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
    } catch (e) {
        alert(e?.response?.data?.message || e.message);
    }
}

async function onOpenTopic() {
    if (!id.value) return;
    try {
        await share.reopenTopic(id.value);
    } catch (e) {
        alert(e?.response?.data?.message || e.message);
    }
}

// Add expense
const desc = ref('');
const amount = ref(null);
const creating = ref(false);
const canCreate = computed(() => !!desc.value && Number(amount.value) > 0);

async function addExpense() {
    if (!id.value || !canCreate.value || isClosed.value) return;
    creating.value = true;
    try {
        await share.createExpense(id.value, { description: desc.value, amount: amount.value });
        await share.fetchExpenses(id.value);
        desc.value = '';
        amount.value = null;
    } finally {
        creating.value = false;
    }
}
</script>
