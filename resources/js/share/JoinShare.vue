<!-- resources/js/share/JoinShare.vue -->
<template>
    <div class="p-3 p-md-4 d-flex justify-content-center">
        <div class="w-100" style="max-width: 720px;">
            <!-- Page header -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="mb-0">Join Shared Topic</h2>
                <RouterLink class="btn btn-link" :to="{ name: 'share.list' }">← Back to Topics</RouterLink>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">

                    <!-- Error -->
                    <div v-if="error" class="alert alert-danger d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-x-circle-fill"></i>
                        <div>{{ error }}</div>
                    </div>

                    <!-- Already member -->
                    <div
                        v-else-if="status === 'already_member'"
                        class="d-flex align-items-center justify-content-between alert alert-success mb-0"
                    >
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <div>You’re already a member. Opening the topic…</div>
                        </div>
                        <RouterLink
                            v-if="topicId"
                            class="btn btn-light btn-sm"
                            :to="{ name: 'share.topic', params: { id: topicId } }"
                        >
                            Open now
                        </RouterLink>
                    </div>

                    <!-- Pending -->
                    <div v-else-if="status === 'pending'" class="alert alert-info mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-hourglass-split mt-1"></i>
                            <div>
                                <div class="fw-semibold mb-1">Request sent to the owner</div>
                                <div class="small text-muted">
                                    You’ll get access once your request is approved. You can safely close this page.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approved (immediate) -->
                    <div
                        v-else-if="status === 'approved'"
                        class="d-flex align-items-center justify-content-between alert alert-success mb-0"
                    >
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-stars"></i>
                            <div>Request approved! Opening the topic…</div>
                        </div>
                        <RouterLink
                            v-if="topicId"
                            class="btn btn-light btn-sm"
                            :to="{ name: 'share.topic', params: { id: topicId } }"
                        >
                            Open now
                        </RouterLink>
                    </div>

                    <!-- Explicit request-needed (not used by current backend, kept for future) -->
                    <div v-else-if="status === 'need_request'" class="p-2">
                        <h5 class="mb-2">Request Access</h5>
                        <p class="text-muted small mb-3">
                            You’re not a member of this topic yet. Send a short note to the owner:
                        </p>
                        <JoinRequestPanel :topic-id="topicId" initial-status="request" />
                    </div>

                    <!-- Loading / Fallback -->
                    <div v-else class="mb-0">
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                            <span>Processing invite link…</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer helper actions when pending (optional quick link) -->
            <div v-if="status === 'pending'" class="text-end mt-3">
                <RouterLink class="btn btn-outline-secondary btn-sm" :to="{ name: 'share.list' }">
                    Go to My Topics
                </RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useShareStore } from '@/stores/share'
import JoinRequestPanel from '@/share/JoinRequestPanel.vue'

const route = useRoute()
const router = useRouter()
const share  = useShareStore()

const error   = ref(null)
const status  = ref(null)   // 'already_member' | 'pending' | 'approved' | 'need_request' | null
const topicId = ref(null)

onMounted(async () => {
    const token = route.params.token
    if (!token) {
        error.value = 'Invalid invite link.'
        return
    }

    try {
        const res = await share.joinByToken(token)
        status.value  = res?.status || null
        topicId.value = res?.topic_id || null

        // Auto-redirect if user can enter immediately
        if ((status.value === 'already_member' || status.value === 'approved') && topicId.value) {
            setTimeout(() =>
                    router.replace({ name: 'share.topic', params: { id: topicId.value } }),
                1200)
        }
        // If 'pending' → stay put and show info
    } catch (e) {
        error.value = e?.response?.data?.message || e.message || 'Failed to process invite.'
    }
})
</script>


