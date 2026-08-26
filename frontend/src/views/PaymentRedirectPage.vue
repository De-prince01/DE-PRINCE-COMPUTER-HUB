<template>
  <div class="max-w-2xl mx-auto px-4 py-10 space-y-6">
    <div>
      <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Payment Status</h1>
      <p class="mt-1 text-sm text-slate-600">We’re processing your payment result.</p>
    </div>

    <div v-if="error" class="card p-4 border-rose-200 bg-rose-50 text-rose-700 text-sm">{{ error }}</div>

    <div class="card p-6 space-y-4">
      <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
          <div class="text-sm font-semibold text-slate-700">Invoice</div>
          <div class="font-extrabold text-slate-900">{{ invoiceNumber || '—' }}</div>
        </div>
        <div class="space-y-1 text-right">
          <div class="text-sm font-semibold text-slate-700">Status</div>
          <div class="font-extrabold text-slate-900">{{ statusLabel }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <div class="text-sm text-slate-700">
          <span class="font-semibold">tx_ref:</span> {{ txRef || '—' }}
        </div>
        <div class="text-sm text-slate-700">
          <span class="font-semibold">transaction_id:</span> {{ transactionId || '—' }}
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button class="btn btn-secondary" @click="verify" :disabled="verifying || !transactionId">
          {{ verifying ? 'Verifying…' : 'Verify with API' }}
        </button>

        <div class="flex items-center gap-2">
          <router-link v-if="invoiceNumber" class="btn btn-primary" :to="`/invoices/${invoiceNumber}/pay`">
            Back to Invoice
          </router-link>
          <router-link v-else class="btn btn-primary" to="/">Go Home</router-link>
        </div>
      </div>

      <div v-if="result" class="card p-4 bg-slate-50 border-slate-200">
        <div class="text-sm font-semibold text-slate-700">API Response</div>
        <pre class="mt-2 text-xs whitespace-pre-wrap text-slate-700">{{ result }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { formatApiError } from '../lib/api'

const route = useRoute()

const verifying = ref(false)
const error = ref('')
const result = ref('')

const invoiceNumber = computed(() => String(route.query.invoice || '').trim() || '')
const txRef = computed(() => String(route.query.tx_ref || '').trim() || '')
const transactionId = computed(() => String(route.query.transaction_id || '').trim() || '')
const status = computed(() => String(route.query.status || '').trim() || '')

const statusLabel = computed(() => {
  if (status.value) return status.value
  if (transactionId.value || txRef.value) return 'pending'
  return 'unknown'
})

async function verify() {
  error.value = ''
  result.value = ''
  if (!transactionId.value) {
    error.value = 'No transaction id found to verify.'
    return
  }

  verifying.value = true
  try {
    const { data } = await api.post('/api/payments/verify/flutterwave', {
      transaction_id: transactionId.value,
      tx_ref: txRef.value,
      invoice_number: invoiceNumber.value || null,
      status: status.value || null,
    })
    result.value = JSON.stringify(data, null, 2)
  } catch (e) {
    const code = e?.response?.status
    if (code === 404) {
      error.value =
        'Verification endpoint not found: POST /api/payments/verify/flutterwave. Update the frontend to match your backend verify route.'
    } else {
      error.value = formatApiError(e, 'Failed to verify payment')
    }
  } finally {
    verifying.value = false
  }
}

onMounted(() => {
  if (statusLabel.value === 'successful') verify()
})
</script>
