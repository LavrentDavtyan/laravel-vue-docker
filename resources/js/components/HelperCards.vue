<template>
    <section class="insights-wide">
        <!-- Header with AI/Math badge and period -->
        <div class="d-flex align-items-end justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <h3 class="mb-0">Smart Suggestions</h3>
                <span v-if="adviceSource"
                      class="badge rounded-pill ms-2"
                      :class="adviceSource==='ai' ? 'text-bg-success' : 'text-bg-warning'">
                  {{ adviceSource==='ai' ? 'AI' : 'Math' }}
                </span>
            </div>
            <small class="text-muted">Period: {{ windowText }}</small>
        </div>
        <p class="text-muted mb-3">
            Personalized advice based on your latest spending behavior.
        </p>

        <div v-if="adviceLoading" class="text-muted">Thinking…</div>
        <div v-else-if="!advice.length" class="empty-state">
            🎉 No suggestions available for this period.
        </div>

        <div v-else class="row g-3">
            <div class="col-md-6 col-xl-4" v-for="(a, idx) in advice" :key="idx">
                <div class="card p-3 shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">{{ a.title }}</div>
                        <span class="badge"
                              :class="{
                                'text-bg-danger': a.severity === 'high',
                                'text-bg-warning': a.severity === 'medium',
                                'text-bg-success': a.severity === 'low'
                              }">{{ a.severity }}</span>
                    </div>
                    <p class="mt-2 mb-1">{{ a.insight }}</p>
                    <p class="small text-muted" v-if="a.why">{{ a.why }}</p>
                    <ul class="small mb-0">
                        <li v-for="(act, i) in a.actions" :key="i">{{ act }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '../http'

const route = useRoute()
const advice = ref([])
const adviceSource = ref('')
const adviceLoading = ref(false)

const windowText = computed(() => {
    const f = route.query.date_from
    const t = route.query.date_to
    return f && t ? `${f} → ${t}` : 'This week'
})

async function loadAdvice() {
    adviceLoading.value = true
    advice.value = []
    adviceSource.value = ''
    try {
        const body = {}
        if (route.query.date_from) body.date_from = route.query.date_from
        if (route.query.date_to)   body.date_to   = route.query.date_to

        const res = await axios.post('/helper/advice', body)
        if (Array.isArray(res.data)) {
            // Backward compatibility (old API shape)
            advice.value = res.data
            adviceSource.value = 'ai'
        } else {
            advice.value = Array.isArray(res.data.items) ? res.data.items : []
            adviceSource.value = res.data.source || 'ai'
        }
        if (!advice.value.length) throw new Error('empty-advice')
    } catch (_) {
        // Fallback to math-based overspend endpoint
        try {
            const res = await axios.get('/helper/overspend', { params: route.query })
            const rows = Array.isArray(res.data) ? res.data : []
            advice.value = rows.map(r => ({
                title: `${r.category || 'Uncategorized'} Overspend`,
                category: r.category || null,
                insight: r.message || 'Spending above baseline.',
                why: 'Detected higher than 4-week average.',
                actions: ['Set a category budget', 'See details'],
                severity: r.delta_pct >= 50 ? 'high' : (r.delta_pct >= 20 ? 'medium' : 'low'),
            }))
            adviceSource.value = 'fallback'
        } catch (e) {
            advice.value = []
            adviceSource.value = ''
        }
    } finally {
        adviceLoading.value = false
    }
}

onMounted(loadAdvice)
watch(() => route.query, loadAdvice, { deep: true })
</script>

<style scoped>
.insights-wide { margin-top: 1.25rem; }
.empty-state {
    padding: 1rem;
    border: 1px dashed #e5e7eb;
    border-radius: 8px;
    text-align: center;
    background: #fafafa;
}
.card { border: 1px solid #e9ecef; border-radius: 12px; }
.badge { font-weight: 600; }
</style>
