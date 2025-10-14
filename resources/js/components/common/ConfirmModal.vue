<template>
    <div class="modal fade" tabindex="-1" ref="modalEl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="btn-close" @click="hide"></button>
                </div>
                <div class="modal-body">
                    <p>{{ message }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="hide">Cancel</button>
                    <button type="button" class="btn btn-danger" @click="confirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({ title: String, message: String });
const emit = defineEmits(['confirm']);
const modalEl = ref(null);
let modal;

onMounted(() => { modal = new Modal(modalEl.value); });

function show() { modal.show(); }
function hide() {
    document.activeElement?.blur()
    modal.hide()
}
function confirm() { emit('confirm'); hide(); }

defineExpose({ show, hide });
</script>
