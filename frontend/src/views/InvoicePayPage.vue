<template>
  <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Pay Invoice</h1>
        <p class="text-sm text-slate-600">Invoice {{ number }}</p>
      </div>
      <router-link to="/" class="btn btn-secondary">Back</router-link>
    </div>

    <div v-if="error" class="card p-4 border-rose-200 bg-rose-50 text-rose-700 text-sm">{{ error }}</div>

    <div class="card p-6 space-y-4">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
          <div class="text-sm font-semibold text-slate-700">Invoice</div>
          <div class="font-extrabold text-slate-900">{{ invoice?.number || number }}</div>
        </div>
        <div class="space-y-1 sm:text-right">
          <div class="text-sm font-semibold text-slate-700">Balance</div>
          <div class="text-xl font-extrabold text-slate-900">₦{{ balanceLabel }}</div>
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button class="btn btn-secondary" @click="loadInvoice" :disabled="loading">
          {{ loading ? 'Loading…' : 'Refresh' }}
        </button>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <button class="btn btn-primary" @click="startFlutterwave" :disabled="payDisabled">
            {{ paying ? 'Opening…' : 'Pay with Flutterwave' }}
          </button>
        </div>
      </div>

      <div v-if="paymentStatus" class="text-sm text-slate-700">
        <span class="font-semibold">Payment:</span> {{ paymentStatus }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { formatApiError } from '../lib/api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  number: { type: String, required: true },
})

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const pay = inject('pay', null)

const loading = ref(false)
const paying = ref(false)
const error = ref('')
const invoice = ref(null)
const paymentStatus = ref('')

const balanceAmount = computed(() => {
  const raw = invoice.value?.balance ?? invoice.value?.total ?? 0
  const n = Number(raw)
  return Number.isFinite(n) ? n : 0
})

const balanceLabel = computed(() => balanceAmount.value.toLocaleString())

const payDisabled = computed(() => paying.value || !pay?.openFlutterwave || balanceAmount.value <= 0)

async function loadInvoice() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await api.get(`/api/invoices/${encodeURIComponent(props.number)}`)
    invoice.value = data.data || data.invoice || data
  } catch (e) {
    invoice.value = null
    error.value = formatApiError(e, 'Failed to load invoice')
  } finally {
    loading.value = false
  }
}

function buildTxRef() {
  return `${props.number}-${Date.now()}`
}

function handleFlutterwaveSuccess(ev) {
  const detail = ev?.detail || {}
  const txRef = detail.tx_ref || detail.txRef || buildTxRef()
  const transactionId = detail.transaction_id || detail.transactionId || detail.id
  const status = detail.status || 'successful'
  paymentStatus.value = status

  router.replace({
    name: 'payment-redirect',
    query: {
      invoice: props.number,
      tx_ref: txRef,
      transaction_id: transactionId,
      status,
    },
  })
}

function handleFlutterwaveClosed() {
  paying.value = false
}

async function startFlutterwave() {
  error.value = ''
  paymentStatus.value = ''

  if (!auth.token) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  if (!pay?.openFlutterwave) {
    error.value = 'Payment service not available.'
    return
  }

  const amount = balanceAmount.value
  if (!(amount > 0)) {
    error.value = 'Invoice has no balance to pay.'
    return
  }

  paying.value = true
  try {
    const txRef = buildTxRef()
    pay.openFlutterwave({
      amount,
      currency: 'NGN',
      tx_ref: txRef,
      redirect_url: `${window.location.origin}/payments/redirect?invoice=${encodeURIComponent(props.number)}&tx_ref=${encodeURIComponent(txRef)}`,
      customer: {
        email: auth.user?.email || 'customer@example.com',
        name: auth.user?.name || 'Customer',
      },
      meta: {
        invoice_number: props.number,
      },
    })
  } catch (e) {
    paying.value = false
    error.value = e?.message || 'Failed to open payment'
  }
}

onMounted(async () => {
  window.addEventListener('flutterwave:success', handleFlutterwaveSuccess)
  window.addEventListener('flutterwave:closed', handleFlutterwaveClosed)
  await loadInvoice()
})

onBeforeUnmount(() => {
  window.removeEventListener('flutterwave:success', handleFlutterwaveSuccess)
  window.removeEventListener('flutterwave:closed', handleFlutterwaveClosed)
})
</script>
