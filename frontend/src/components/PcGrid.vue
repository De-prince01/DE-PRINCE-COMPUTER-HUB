<template>
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div v-for="pc in pcs" :key="pc.id" class="card p-4">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="font-bold text-slate-900">{{ pc.name }}</div>
          <div class="text-xs text-slate-500">#{{ pc.identifier }}</div>
        </div>
        <span :class="statusBadge(pc.status)">{{ pc.status }}</span>
      </div>

      <div class="mt-3 text-sm text-slate-700">
        <div class="flex items-center justify-between">
          <span>Hourly</span>
          <span class="font-semibold">₦{{ Number(pc.hourly_rate || 0).toLocaleString() }}</span>
        </div>
      </div>

      <div class="mt-4 flex items-center gap-2">
        <button class="btn btn-primary flex-1" :disabled="pc.status !== 'idle'" @click="$emit('start', pc)">
          Start
        </button>
        <button class="btn btn-secondary flex-1" :disabled="pc.status !== 'in_use'" @click="$emit('stop', pc)">
          Stop
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
function statusBadge(status) {
  if (status === 'idle') return 'badge-success'
  if (status === 'in_use') return 'badge-warning'
  if (status === 'maintenance') return 'badge-info'
  return 'badge-muted'
}

defineProps({
  pcs: { type: Array, default: () => [] },
})

defineEmits(['start', 'stop'])
</script>

