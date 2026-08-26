<template>
  <div class="card p-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <div class="font-bold text-slate-900">Active Session</div>
        <div class="text-xs text-slate-500">{{ label }}</div>
      </div>
      <span class="badge-warning">running</span>
    </div>

    <div class="mt-4 text-3xl font-extrabold tabular-nums text-slate-900">
      {{ hh }}:{{ mm }}:{{ ss }}
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useIntervalFn } from '@vueuse/core'

const props = defineProps({
  startedAt: { type: [String, Date], required: true },
  label: { type: String, default: '' },
})

const startMs = computed(() => new Date(props.startedAt).getTime())
const tick = ref(Date.now())

useIntervalFn(() => {
  tick.value = Date.now()
}, 1000)

const elapsed = computed(() => Math.max(0, Math.floor((tick.value - startMs.value) / 1000)))

const hh = computed(() => String(Math.floor(elapsed.value / 3600)).padStart(2, '0'))
const mm = computed(() => String(Math.floor((elapsed.value % 3600) / 60)).padStart(2, '0'))
const ss = computed(() => String(elapsed.value % 60).padStart(2, '0'))
</script>
