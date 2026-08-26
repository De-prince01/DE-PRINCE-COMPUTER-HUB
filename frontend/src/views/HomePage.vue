<template>
  <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Cyber PCs</h1>
        <p class="text-sm text-slate-600">Start a session, stop it, and pay the generated invoice.</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn btn-secondary" @click="loadPcs" :disabled="loading">Refresh</button>
      </div>
    </div>

    <div v-if="error" class="card p-4 border-rose-200 bg-rose-50 text-rose-700 text-sm">{{ error }}</div>

    <div v-if="invoice" class="card p-4 border-emerald-200 bg-emerald-50">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="font-bold text-slate-900">Invoice created</div>
          <div class="text-sm text-slate-700">
            {{ invoice.number }} · Balance ₦{{ Number(invoice.balance || 0).toLocaleString() }}
          </div>
        </div>
        <router-link class="btn btn-primary" :to="`/invoices/${invoice.number}/pay`">Pay</router-link>
      </div>
    </div>

    <PcGrid :pcs="pcs" @start="startSession" @stop="stopSession" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import PcGrid from '../components/PcGrid.vue'
import api, { formatApiError } from '../lib/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const pcs = ref([])
const loading = ref(false)
const error = ref('')
const invoice = ref(null)

async function loadPcs() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await api.get('/api/pcs')
    pcs.value = data.data || data
  } catch (e) {
    error.value = formatApiError(e, 'Failed to load PCs')
  } finally {
    loading.value = false
  }
}

async function startSession(pc) {
  invoice.value = null
  if (!auth.token) {
    error.value = 'Login required to start a session.'
    return
  }
  error.value = ''
  try {
    await api.post('/api/sessions', { pc_id: pc.id })
    await loadPcs()
  } catch (e) {
    error.value = formatApiError(e, 'Failed to start session')
  }
}

async function stopSession(pc) {
  invoice.value = null
  if (!auth.token) {
    error.value = 'Login required to stop a session.'
    return
  }
  error.value = ''
  try {
    const { data } = await api.get(`/api/sessions?pc_id=${pc.id}&status=active`)
    const active = (data.data || data).find((s) => s.status === 'active')
    if (!active) {
      error.value = 'No active session found for this PC.'
      return
    }
    const stop = await api.post(`/api/sessions/${active.id}/stop`)
    invoice.value = stop.data.invoice
    await loadPcs()
  } catch (e) {
    error.value = formatApiError(e, 'Failed to stop session')
  }
}

onMounted(loadPcs)
</script>
