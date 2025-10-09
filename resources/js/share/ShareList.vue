<template>
    <div class="p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Share Expenses — Topics</h2>
            <button class="btn btn-primary" :disabled="loading" @click="toggleNew = !toggleNew">
                New Topic
            </button>
        </div>

        <div v-if="toggleNew" class="card card-body mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label">Title</label>
                    <input v-model="title" class="form-control" placeholder="Trip to Dilijan" />
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Currency</label>
                    <input v-model="currency" maxlength="3" class="form-control" placeholder="USD" />
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-success" :disabled="creating" @click="create">
                        {{ creating ? 'Creating…' : 'Create' }}
                    </button>
                </div>
            </div>
            <div v-if="error" class="text-danger small mt-2">{{ error }}</div>
        </div>

        <div v-if="loading" class="alert alert-secondary">Loading…</div>
        <div v-else-if="!topics.length" class="alert alert-info">No topics yet.</div>

        <div v-else class="row g-3">
            <div v-for="t in topics" :key="t.id" class="col-12 col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-1">{{ t.title }}</h5>
                        <div class="text-muted small mb-3">
                            Currency: {{ t.currency }} · Members: {{ t.members }}
                        </div>
                        <RouterLink
                            class="btn btn-outline-primary mt-auto"
                            :to="{ name: 'share.topic', params: { id: t.id } }"
                        >
                            Open Topic
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useShareStore } from '@/stores/share';

const share = useShareStore();
const { topics, loading, error } = storeToRefs(share);

const toggleNew = ref(false);
const title = ref('');
const currency = ref('USD');
const creating = ref(false);

const create = async () => {
    if (!title.value.trim() || !currency.value.trim()) return;
    creating.value = true;
    try {
        await share.createTopic({
            title: title.value.trim(),
            currency: currency.value.trim().toUpperCase(),
        });
        title.value = '';
        currency.value = 'USD';
        toggleNew.value = false;
    } finally {
        creating.value = false;
    }
};

onMounted(() => {
    share.fetchTopics().catch(() => {});
});
</script>
