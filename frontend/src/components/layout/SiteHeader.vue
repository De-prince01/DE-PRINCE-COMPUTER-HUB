<template>
  <header class="bg-white border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
      <router-link to="/" class="font-extrabold tracking-tight text-slate-900">
        DE-PRINCE HUB
      </router-link>
      <nav class="flex items-center gap-3">
        <router-link to="/" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Home</router-link>
        <template v-if="!auth.token">
          <router-link to="/login" class="btn btn-secondary">Login</router-link>
          <router-link to="/register" class="btn btn-primary">Create account</router-link>
        </template>
        <button v-else class="btn btn-secondary" @click="onLogout">Logout</button>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function onLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
