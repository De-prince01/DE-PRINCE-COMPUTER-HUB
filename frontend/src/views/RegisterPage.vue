<template>
  <div class="max-w-md mx-auto px-4 py-12">
    <div class="card p-6">
      <h1 class="text-xl font-extrabold text-slate-900">Create account</h1>
      <p class="mt-1 text-sm text-slate-600">Create a new account to continue.</p>

      <form class="mt-6 space-y-4" @submit.prevent="onSubmit">
        <div>
          <label class="text-sm font-semibold text-slate-700">Name</label>
          <input v-model="name" type="text" class="input mt-1" required />
        </div>

        <div>
          <label class="text-sm font-semibold text-slate-700">Email</label>
          <input v-model="email" type="email" class="input mt-1" required />
        </div>

        <div>
          <label class="text-sm font-semibold text-slate-700">Phone (optional)</label>
          <input v-model="phone" type="tel" class="input mt-1" />
        </div>

        <div>
          <label class="text-sm font-semibold text-slate-700">Role</label>
          <select v-model="role" class="input mt-1">
            <option value="customer">Customer</option>
            <option value="vendor">Vendor</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-semibold text-slate-700">Password</label>
          <input v-model="password" type="password" class="input mt-1" required minlength="8" />
        </div>

        <div>
          <label class="text-sm font-semibold text-slate-700">Confirm password</label>
          <input v-model="passwordConfirmation" type="password" class="input mt-1" required minlength="8" />
        </div>

        <div v-if="error" class="text-sm text-rose-700">{{ error }}</div>

        <button class="btn btn-primary w-full" :disabled="loading">
          {{ loading ? 'Creating…' : 'Create account' }}
        </button>

        <div class="text-sm text-slate-600 text-center">
          Already have an account?
          <router-link to="/login" class="font-semibold text-slate-900 hover:underline">Login</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { formatApiError } from '../lib/api'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const phone = ref('')
const role = ref('customer')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref('')

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      phone: phone.value || null,
      role: role.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push('/')
  } catch (e) {
    error.value = formatApiError(e, 'Registration failed')
  } finally {
    loading.value = false
  }
}
</script>
