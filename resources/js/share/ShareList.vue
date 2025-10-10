<!-- resources/js/share/ShareList.vue -->
<template>
    <div class="p-3 p-md-4">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="mb-1">Shared Topics</h2>
                <div class="text-muted small">
                    Manage shared expense topics, invite friends, and track balances.
                </div>
            </div>
            <RouterLink class="back-link" to="/dashboard">← Back to Dashboard</RouterLink>
        </div>

        <!-- Create Topic -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">New</span>
                        Create a Topic
                    </h5>
                    <button class="btn btn-outline-secondary btn-sm" :disabled="loading" @click="refresh">
                        {{ loading ? 'Refreshing…' : 'Refresh' }}
                    </button>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Title</label>
                        <input
                            v-model="title"
                            class="form-control"
                            placeholder="Trip to Dilijan"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label">Currency</label>
                        <select v-model="currency" class="form-select">
                            <option value="USD">USD ($)</option>
                            <option value="AMD">AMD (֏)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-auto">
                        <button
                            class="btn btn-primary"
                            :disabled="creating || !canCreate"
                            @click="onCreate"
                        >
                            {{ creating ? 'Creating…' : 'Create Topic' }}
                        </button>
                    </div>
                </div>

                <small class="text-muted d-block mt-2">
                    You’ll be added as the owner automatically.
                </small>

                <div v-if="error" class="text-danger small mt-2">{{ error }}</div>
            </div>
        </div>

        <!-- Quick stats -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Topics</div>
                        <div class="h4 mb-0">{{ topics.length }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Open</div>
                        <div class="h4 mb-0">{{ openCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Closed</div>
                        <div class="h4 mb-0">{{ closedCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Last Updated</div>
                        <div class="h5 mb-0">{{ lastUpdatedLabel }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!topics.length && !loading" class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-2">No topics yet.</div>
                <div class="text-muted mb-3">Create your first shared expense topic above.</div>
                <button class="btn btn-outline-primary btn-sm" @click="title='Weekend Trip'; currency='USD'">
                    Use example title
                </button>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-else-if="loading" class="row g-3">
            <div class="col-12 col-md-6 col-lg-4" v-for="n in 6" :key="'sk'+n">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="placeholder-glow">
                            <span class="placeholder col-8"></span>
                            <div class="mt-3">
                                <span class="placeholder col-5"></span>
                            </div>
                            <div class="d-flex justify-content-between mt-4">
                                <span class="placeholder col-4"></span>
                                <span class="placeholder col-3"></span>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <span class="placeholder col-4"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Responsive cards on small screens; table on md+ -->
        <div v-else>
            <!-- Card grid (shown < md) -->
            <div class="row g-3 d-md-none">
                <div v-for="t in topics" :key="'card-'+t.id" class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h5 class="card-title mb-0">
                                    #{{ t.id }} — {{ t.title }}
                                </h5>
                                <span class="badge" :class="t.status === 'closed' ? 'bg-danger' : 'bg-success'">
                  {{ capitalize(t.status || 'open') }}
                </span>
                            </div>
                            <div class="text-muted small">Currency: <strong>{{ t.currency || 'USD' }}</strong></div>
                            <div class="text-muted small">Members: <strong>{{ t.members }}</strong></div>

                            <div class="mt-3 d-flex justify-content-end gap-2">
                                <RouterLink
                                    class="btn btn-primary btn-sm"
                                    :to="{ name: 'share.topic', params: { id: t.id } }"
                                >
                                    Open
                                </RouterLink>
                                <button
                                    class="btn btn-outline-secondary btn-sm"
                                    @click="copyInvite(t.id)"
                                    :disabled="copyingId === t.id"
                                    title="Copy invite link (owner only)"
                                >
                                    {{ copyingId === t.id ? 'Copying…' : 'Copy Invite' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table (shown ≥ md) -->
            <div class="card d-none d-md-block border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="mb-0">Your Topics</h5>
                        <button class="btn btn-sm btn-outline-secondary" :disabled="loading" @click="refresh">
                            {{ loading ? 'Refreshing…' : 'Refresh' }}
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th style="min-width: 260px;">Title</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th class="text-end">Members</th>
                                <th class="text-end" style="min-width: 200px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="t in topics" :key="t.id">
                                <td class="fw-medium">
                                    <span class="badge bg-primary-subtle text-primary me-2 rounded-pill px-2">#{{ t.id }}</span>
                                    {{ t.title }}
                                </td>
                                <td>{{ t.currency || 'USD' }}</td>
                                <td>
                  <span class="badge" :class="t.status === 'closed' ? 'bg-danger' : 'bg-success'">
                    {{ capitalize(t.status || 'open') }}
                  </span>
                                </td>
                                <td class="text-end">{{ t.members }}</td>
                                <td class="text-end">
                                    <RouterLink
                                        class="btn btn-sm btn-primary me-2"
                                        :to="{ name: 'share.topic', params: { id: t.id } }"
                                    >
                                        Open
                                    </RouterLink>
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="copyInvite(t.id)"
                                        :disabled="copyingId === t.id"
                                        title="Copy invite link (owner only)"
                                    >
                                        {{ copyingId === t.id ? 'Copying…' : 'Copy Invite' }}
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <!-- Toast -->
        <div
            v-if="toastVisible"
            class="alert"
            :class="toastType === 'success' ? 'alert-success' : 'alert-danger'"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 1050; min-width: 260px;"
        >
            {{ toastMessage }}
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useShareStore } from '@/stores/share';

const share = useShareStore();
const { topics, loading, error, inviteToken } = storeToRefs(share);

/* ------- Create topic ------- */
const title = ref('');
const currency = ref('USD');
const creating = ref(false);
const canCreate = computed(() => (title.value || '').trim().length > 0);

async function onCreate() {
    if (!canCreate.value) return;
    creating.value = true;
    try {
        await share.createTopic({ title: title.value.trim(), currency: currency.value });
        title.value = '';
        currency.value = 'USD';
        showToast('Topic created.', 'success');
    } catch (e) {
        showToast(share.error || 'Failed to create topic.', 'danger');
    } finally {
        creating.value = false;
    }
}

/* ------- Copy invite link (owner only) ------- */
const copyingId = ref(null);
async function copyInvite(topicId) {
    copyingId.value = topicId;
    try {
        // rotate to ensure a fresh token (works only if you are the owner)
        const res = await share.rotateInvite(topicId); // sets inviteToken in store
        const token = res?.invite_token || inviteToken.value;
        if (!token) {
            showToast('Only the owner can rotate/copy the invite link.', 'danger');
            return;
        }
        const url = `${window.location.origin}/share/join/${token}`;
        await navigator.clipboard.writeText(url);
        showToast('Invite link copied to clipboard.');
    } catch (e) {
        showToast('Could not copy invite link.', 'danger');
    } finally {
        copyingId.value = null;
    }
}

/* ------- Derived stats ------- */
const openCount = computed(() => topics.value.filter(t => (t.status || 'open') === 'open').length);
const closedCount = computed(() => topics.value.filter(t => t.status === 'closed').length);
const lastUpdatedLabel = computed(() => {
    // Simple “now” label—replace with a real timestamp if you track it
    return new Date().toLocaleTimeString();
});

/* ------- List refresh ------- */
async function refresh() {
    await share.fetchTopics();
}

/* ------- Utilities ------- */
function capitalize(s) {
    return (s || '').charAt(0).toUpperCase() + (s || '').slice(1);
}

/* ------- Toast ------- */
const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
function showToast(msg, type = 'success') {
    toastMessage.value = msg;
    toastType.value = type;
    toastVisible.value = true;
    setTimeout(() => (toastVisible.value = false), 2200);
}

/* ------- Initial load ------- */
onMounted(async () => {
    if (!topics.value.length) {
        await share.fetchTopics();
    }
});
</script>

<style scoped>
/* Subtle badge for the “New” label in the create card (works with Bootstrap 5.3’s color modes) */
.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.08);
}
.text-primary {
    color: #0d6efd !important;
}
.border-primary-subtle {
    border-color: rgba(13, 110, 253, 0.2) !important;
}

/* Improve placeholder visibility even on light themes */
.placeholder {
    display: inline-block;
    background-color: rgba(0,0,0,0.08);
    border-radius: .375rem;
    height: 1rem;
}
</style>
