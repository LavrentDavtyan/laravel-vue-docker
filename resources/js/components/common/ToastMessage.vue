<template>
    <transition name="fade">
        <div
            v-if="visible"
            class="toast align-items-center text-white border-0"
            :class="type === 'success' ? 'bg-success' : 'bg-danger'"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 2000; min-width: 250px;"
        >
            <div class="d-flex">
                <div class="toast-body">{{ message }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="visible=false"></button>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref } from 'vue';

const visible = ref(false);
const message = ref('');
const type = ref('success');

function show(msg, t = 'success') {
    message.value = msg;
    type.value = t;
    visible.value = true;
    setTimeout(() => (visible.value = false), 3000);
}

defineExpose({ show });
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .5s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
