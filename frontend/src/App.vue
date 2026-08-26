<template>
  <div class="min-h-screen flex flex-col">
    <SiteHeader />
    <main class="flex-1">
      <router-view />
    </main>
    <SiteFooter />
  </div>
</template>

<script setup>
import { provide } from 'vue'
import SiteHeader from './components/layout/SiteHeader.vue'
import SiteFooter from './components/layout/SiteFooter.vue'
import { useAuthStore } from './stores/auth'
import { useFlutterwave } from 'flutterwave-vue3'

const auth = useAuthStore()
auth.restore()

function openFlutterwave(cfg) {
  const publicKey = import.meta.env.VITE_FLW_PUBLIC_KEY
  if (!publicKey) throw new Error('VITE_FLW_PUBLIC_KEY is not set')

  useFlutterwave({
    public_key: publicKey,
    payment_options: 'card,banktransfer,ussd,account,qr',
    redirect_url: undefined,
    customizations: { title: 'DE-PRINCE HUB', description: 'Secure payment', logo: '' },
    callback(response) {
      window.dispatchEvent(new CustomEvent('flutterwave:success', { detail: response }))
    },
    onclose() {
      window.dispatchEvent(new CustomEvent('flutterwave:closed'))
    },
    ...cfg,
  })
}

provide('pay', { openFlutterwave })
</script>
