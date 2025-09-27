<template>
  <div class="expense-form">
    <h3>{{ isEdit ? 'Edit Expense' : 'Add Expense' }}</h3>
    <form @submit.prevent="handleSubmit">
      <input v-model="localForm.amount" type="number" step="0.01" placeholder="Amount" required />
      <input v-model="localForm.category" type="text" placeholder="Category" required />
      <input v-model="localForm.description" type="text" placeholder="Description" />
      <input v-model="localForm.date" type="date" required />
      <button type="submit">{{ isEdit ? 'Update Expense' : 'Add Expense' }}</button>
      <button v-if="isEdit" type="button" @click="handleCancel">Cancel</button>
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
  const res = await axios.get(`/api/expenses/${id}`)
  localForm.value = { ...res.data }
}

onMounted(() => {
  if (isEdit.value) {
    fetchExpense(route.params.id)
  }
})

const handleSubmit = async () => {
  if (isEdit.value) {
    await axios.put(`/api/expenses/${localForm.value.id}`, localForm.value)
  } else {
    await axios.post('/api/expenses', localForm.value)
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
form {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
