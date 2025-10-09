<template>
    <div class="p-4">
        <h3 class="mb-3">Joining shared topic…</h3>
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-else class="alert alert-info">Processing invite token…</div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useShareStore } from '@/stores/share';

const route = useRoute();
const router = useRouter();
const share = useShareStore();
const error = ref(null);

onMounted(async () => {
    const token = route.params.token;
    try {
        const { topic_id } = await share.joinByToken(token);
        router.replace(`/share/${topic_id}`);
    } catch (e) {
        error.value = e?.response?.data?.message || e.message;
    }
});
</script>
