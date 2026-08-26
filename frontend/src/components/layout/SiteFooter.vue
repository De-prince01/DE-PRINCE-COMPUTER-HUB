<template>
  <footer class="border-t border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-slate-600">
      <div class="flex items-center justify-between">
        <span>© {{ year }} DE-PRINCE HUB</span>
        <span class="text-slate-500">Laravel API + Vue SPA</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear()
</script>

