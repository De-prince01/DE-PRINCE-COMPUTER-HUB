<template>
  <div class="max-w-md mx-auto px-4 py-12">
    <div class="card p-6">
      <h1 class="text-xl font-extrabold text-slate-900">Login</h1>
      <p class="mt-1 text-sm text-slate-600">Use your API account.</p>

      <form class="mt-6 space-y-4" @submit.prevent="onSubmit">
        <div>
          <label class="text-sm font-semibold text-slate-700">Email</label>
          <input v-model="email" type="email" class="input mt-1" required />
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Password</label>
          <input v-model="password" type="password" class="input mt-1" required />
        </div>

        <div v-if="error" class="text-sm text-rose-700">{{ error }}</div>

        <button class="btn btn-primary w-full" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Login' }}
        </button>
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

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login({ email: email.value, password: password.value })
    router.push('/')
  } catch (e) {
    error.value = formatApiError(e, 'Login failed')
  } finally {
    loading.value = false
  }
}
</script>
