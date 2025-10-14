<template>
    <div class="modal fade" tabindex="-1" ref="modalEl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body p-4">
                    <h5 class="fw-semibold mb-3">{{ title }}</h5>
                    <p class="text-muted mb-4">{{ message }}</p>
                    <button type="button" class="btn btn-primary px-4" @click="hide">OK</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Modal } from 'bootstrap'

const props = defineProps({ title: String, message: String })
const modalEl = ref(null)
let modal

onMounted(() => { modal = new Modal(modalEl.value) })

function show() { modal.show() }
function hide() {
    document.activeElement?.blur()
    modal.hide()
}

defineExpose({ show, hide })
</script>
