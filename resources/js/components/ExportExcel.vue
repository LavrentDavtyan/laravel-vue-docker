<template>
    <button
        class="btn btn-success export-btn d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-sm"
        :disabled="loading"
        @click="exportExcel"
        :title="`Export ${label} to Excel`"
    >
        <!-- Excel Icon -->
        <svg
            v-if="!loading"
            class="excel-icon"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 48 48"
            width="20"
            height="20"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#21a366"/>
                    <stop offset="1" stop-color="#107c41"/>
                </linearGradient>
            </defs>
            <rect x="10" y="6" width="30" height="36" rx="3" ry="3" fill="url(#g)" />
            <rect x="6" y="10" width="18" height="28" rx="2" ry="2" fill="#185c37"/>
            <path
                d="M12 32l5-8-5-8h4l3 6 3-6h4l-5 8 5 8h-4l-3-6-3 6h-4z"
                fill="#fff"
            />
        </svg>

        <!-- Loading spinner -->
        <span v-else class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>

        <span class="fw-semibold">{{ loading ? `Exporting ${label}…` : `Export ${label}` }}</span>
    </button>
</template>

<script setup>
import { ref } from 'vue'
import axios from '../http'

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (v) => ['expenses', 'incomes'].includes(v)
    }
})

const loading = ref(false)
const label = props.type.charAt(0).toUpperCase() + props.type.slice(1)

const exportExcel = async () => {
    try {
        loading.value = true

        const res = await axios.get(`/exports/${props.type}`, { responseType: 'blob' })
        const now = new Date()
        const yyyy = now.getFullYear()
        const mm = String(now.getMonth() + 1).padStart(2, '0')
        const dd = String(now.getDate()).padStart(2, '0')
        const filename = `${props.type}_${yyyy}-${mm}-${dd}.xlsx`

        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
    } catch (e) {
        console.error(`${label} export failed:`, e)
        alert(`Export ${label} failed. Please try again.`)
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.export-btn {
    transition: transform .08s ease, box-shadow .12s ease, filter .12s ease;
}
.export-btn:hover {
    filter: brightness(1.03);
    transform: translateY(-1px);
    box-shadow: 0 .5rem 1rem rgba(16,124,65,.2);
}
.export-btn:active {
    transform: translateY(0);
    box-shadow: 0 .25rem .5rem rgba(16,124,65,.18);
}
.export-btn:disabled {
    opacity: .8;
    cursor: not-allowed;
}
.excel-icon {
    display: block;
}
</style>
