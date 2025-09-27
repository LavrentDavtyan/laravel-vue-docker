<template>
  <div class="expense-form container mt-4">
    <h3 class="mb-4">{{ isEdit ? 'Edit Expense' : 'Add Expense' }}</h3>

    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label for="amount" class="form-label">Amount</label>
        <input
          id="amount"
          v-model="localForm.amount"
          type="number"
          step="0.01"
          placeholder="Enter amount"
          class="form-control"
          required
        />
      </div>

      <div class="mb-3">
        <label for="category" class="form-label">Category</label>
        <input
          id="category"
          v-model="localForm.category"
          type="text"
          placeholder="Enter category"
          class="form-control"
          required
        />
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <input
          id="description"
          v-model="localForm.description"
          type="text"
          placeholder="Optional description"
          class="form-control"
        />
      </div>

      <div class="mb-3">
        <label for="date" class="form-label">Date</label>
        <input
          id="date"
          v-model="localForm.date"
          type="date"
          class="form-control"
          required
        />
      </div>


      <div class="d-flex justify-content-center gap-2">
        <button
          type="button"
          class="btn btn-secondary mr-2"
          @click="handleCancel"
        >
          Cancel
        </button>
        <button type="submit" class="btn btn-primary">
          {{ isEdit ? 'Update Expense' : 'Add Expense' }}
        </button>
      </div>
    </form>
  </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const localForm = ref({
  id: null,
  amount: '',
  category: '',
  description: '',
  date: ''
})

const fetchExpense = async (id) => {
  const res = await axios.get(`/expenses/${id}`)
  localForm.value = { ...res.data }
}

onMounted(() => {
  if (isEdit.value) {
    fetchExpense(route.params.id)
  }
})

const handleSubmit = async () => {
  if (isEdit.value) {
    await axios.put(`/expenses/${localForm.value.id}`, localForm.value)
  } else {
    await axios.post('/expenses', localForm.value)
  }
  router.push('/expenses')
}

const handleCancel = () => {
  router.push('/expenses')
}
</script>

<style scoped>
.expense-form {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin: 2rem auto;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  max-width: 500px;
}

</style>
