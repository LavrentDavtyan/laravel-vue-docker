<template>
  <div class="income-form container mt-4">
      <h3 class="mb-4">{{ isEdit ? 'Edit Income' : 'Add Income' }}</h3>

      <form @submit.prevent="handleSubmit" novalidate>
          <!-- Amount -->
          <div class="mb-3">
              <label for="amount" class="form-label">Amount</label>
              <input
                  id="amount"
                  v-model="localForm.amount"
                  type="text"
                  inputmode="decimal"
                  placeholder="Enter amount, e.g. 1500.00"
                  class="form-control"
                  :class="{'is-invalid': errors.amount}"
                  @input="validateField('amount')"
                  @blur="validateField('amount')"
                  required
              />
              <div v-if="errors.amount" class="invalid-feedback d-block">
                  {{ errors.amount }}
              </div>
          </div>

          <!-- Category -->
          <div class="mb-3">
              <label for="category" class="form-label">Category</label>
              <input
                  id="category"
                  v-model.trim="localForm.category"
                  type="text"
                  placeholder="e.g. Salary, Bonus, Gift"
                  class="form-control"
                  :class="{'is-invalid': errors.category}"
                  @input="validateField('category')"
                  @blur="validateField('category')"
                  required
              />
              <div v-if="errors.category" class="invalid-feedback d-block">
                  {{ errors.category }}
              </div>
          </div>

          <!-- Description -->
          <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <input
                  id="description"
                  v-model.trim="localForm.description"
                  type="text"
                  placeholder="Optional short note"
                  class="form-control"
                  :class="{'is-invalid': errors.description}"
                  @input="validateField('description')"
                  @blur="validateField('description')"
              />
              <div v-if="errors.description" class="invalid-feedback d-block">
                  {{ errors.description }}
              </div>
          </div>

          <!-- Date -->
          <div class="mb-3">
              <label for="date" class="form-label">Date</label>
              <input
                  id="date"
                  v-model="localForm.date"
                  type="date"
                  class="form-control"
                  :class="{'is-invalid': errors.date}"
                  @input="validateField('date')"
                  @blur="validateField('date')"
                  required
              />
              <div v-if="errors.date" class="invalid-feedback d-block">
                  {{ errors.date }}
              </div>
          </div>

          <div class="d-flex gap-2">
              <button type="button" class="btn btn-secondary" @click="router.push('/incomes')">
                  Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                  {{ isEdit ? 'Save Changes' : 'Add Income' }}
              </button>
          </div>
      </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '../http'

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

const errors = ref({
  amount: '',
  category: '',
  description: '',
  date: ''
})

const submitting = ref(false)

/* ------------------ Validation Rules ------------------ */
const validators = {
  amount(value) {
      if (value === '' || value == null) return 'Amount is required'
      const normalized = String(value).replace(',', '.')
      if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
          return 'Amount must be a number (up to 2 decimals)'
      }
      if (parseFloat(normalized) <= 0) return 'Amount must be greater than 0'
      return ''
  },
  category(value) {
      if (!value || !value.trim()) return 'Category is required'
      if (value.length > 50) return 'Category is too long'
      return ''
  },
  description(value) {
      if (!value) return '' // optional
      if (value.length > 1000) return 'Description is too long (max 1000)'
      return ''
  },
  date(value) {
      if (!value) return 'Date is required'
      if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return 'Date must be valid'
      return ''
  }
}

function validateField(name) {
  const v = localForm.value[name]
  errors.value[name] = validators[name](v)
}

function validateAll() {
  Object.keys(validators).forEach(validateField)
  return Object.values(errors.value).every(msg => !msg)
}

/* ------------------ Data Fetch for Edit ------------------ */
const fetchIncome = async (id) => {
  const res = await axios.get(`/incomes/${id}`)
  localForm.value = {
      id: res.data.id,
      amount: String(res.data.amount),
      category: res.data.category || '',
      description: res.data.description || '',
      date: (res.data.date || '').slice(0, 10)
  }
}

onMounted(() => {
  if (isEdit.value && route.params.id) {
      fetchIncome(route.params.id)
  }
})

/* ------------------ Submit ------------------ */
const handleSubmit = async () => {
  if (!validateAll()) {
      const firstInvalid = Object.entries(errors.value).find(([, msg]) => msg)?.[0]
      if (firstInvalid) document.getElementById(firstInvalid)?.focus()
      return
  }

  submitting.value = true
  try {
      const payload = {
          ...localForm.value,
          amount: String(localForm.value.amount).replace(',', '.')
      }

      if (isEdit.value) {
          await axios.put(`/incomes/${route.params.id}`, payload)
      } else {
          await axios.post('/incomes', payload)
      }
      router.push('/incomes')
  } catch (e) {
      if (e.response?.status === 422 && e.response?.data?.errors) {
          const serverErrors = e.response.data.errors
          for (const key of Object.keys(errors.value)) {
              if (serverErrors[key]?.length) errors.value[key] = serverErrors[key][0]
          }
      } else {
          console.error(e)
          alert('Failed to save income. Please try again.')
      }
  } finally {
      submitting.value = false
  }
}
</script>

<style scoped>
.income-form {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin: 2rem auto;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  max-width: 500px;
}
</style>
