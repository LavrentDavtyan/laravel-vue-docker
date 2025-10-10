Here’s the full file with a couple of safe, UX-only improvements (no breaking changes):
•	Optional initialStatus prop to prefill status when the parent already knows it.
•	Small live character counter.
•	Extra guards to prevent double-submit.

<!-- resources/js/share/JoinRequestPanel.vue -->
<template>
    <div class="card card-body">
        <!-- Already Member -->
        <div v-if="status === 'already_member'" class="alert alert-success mb-0" role="status" aria-live="polite">
            You are already a member of this topic.
        </div>

        <!-- Pending Request -->
        <div v-else-if="status === 'pending'" class="alert alert-warning mb-0" role="status" aria-live="polite">
            Your join request is pending approval from the topic owner.
        </div>

        <!-- Denied -->
        <div v-else-if="status === 'denied'" class="alert alert-danger mb-0" role="status" aria-live="polite">
            Your join request was denied. You can’t access this topic.
        </div>

        <!-- Request Form -->
        <div v-else>
            <h5 class="mb-1">Request to Join</h5>
            <p class="text-muted mb-2">
                Send a join request to the topic owner to gain access.
            </p>

            <textarea
                v-model="message"
                class="form-control mb-2"
                rows="3"
                placeholder="Optional message to the owner"
                :maxlength="MAX_LEN"
            ></textarea>

            <div class="d-flex justify-content-between align-items-center mb-3 small text-muted">
                <span aria-live="polite">{{ remaining }} / {{ MAX_LEN }} characters left</span>
            </div>

            <button
                class="btn btn-primary"
                :disabled="loading || submitted"
                @click="submitRequest"
            >
                {{ loading ? 'Sending…' : submitted ? 'Sent' : 'Send Request' }}
            </button>

            <div v-if="error" class="text-danger mt-2 small" aria-live="polite">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useShareStore } from '@/stores/share';

const MAX_LEN = 500;

const props = defineProps({
    topicId: {
        type: [Number, String],
        default: null,
    },
    // Optional: if parent already knows the status ('pending' | 'denied' | 'already_member')
    initialStatus: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['requested']);

const route = useRoute();
const share = useShareStore();

const loading = ref(false);
const submitted = ref(false);
const error = ref('');
const message = ref('');
const status = ref(null); // 'pending' | 'approved' | 'denied' | 'already_member'

const remaining = computed(() => Math.max(0, MAX_LEN - (message.value?.length || 0)));

function getTopicId() {
    const fromProp = props.topicId != null ? Number(props.topicId) : null;
    if (Number.isFinite(fromProp) && fromProp > 0) return fromProp;
    const fromRoute = Number(route.params.id);
    return Number.isFinite(fromRoute) ? fromRoute : null;
}

async function submitRequest() {
    if (loading.value || submitted.value) return; // guard against double-clicks
    error.value = '';
    const topicId = getTopicId();

    if (!topicId) {
        error.value = 'Missing topic ID.';
        return;
    }

    loading.value = true;
    try {
        const res = await share.createJoinRequest(topicId, { message: message.value || undefined });
        status.value = res?.status || 'pending';
        submitted.value = true;
        emit('requested', { topicId, ...res }); // Parent can refresh lists/toasts as needed
    } catch (e) {
        error.value =
            e?.response?.data?.message ||
            e?.message ||
            'Failed to send request.';
    } finally {
        loading.value = false;
    }
}

// Initialize from parent, if provided
onMounted(() => {
    if (props.initialStatus) {
        status.value = props.initialStatus;
    }
});

// Reset when topic changes (route-level)
watch(
    () => route.fullPath,
    () => {
        loading.value = false;
        submitted.value = false;
        error.value = '';
        message.value = '';
        status.value = props.initialStatus || null;
    }
);

// Also react if parent changes initialStatus later
watch(
    () => props.initialStatus,
    (val) => {
        // Only set if we haven't already submitted in this session
        if (!submitted.value) status.value = val || null;
    }
);
</script>
