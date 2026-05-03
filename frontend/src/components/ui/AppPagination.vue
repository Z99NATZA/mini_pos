<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  currentPage: number
  totalPages: number
  total: number
  perPage: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

// Build smart page number list with ellipsis
const pages = computed(() => {
  const result: (number | '...')[] = []
  const total = props.totalPages
  const current = props.currentPage

  if (total <= 7) {
    for (let i = 1; i <= total; i++) result.push(i)
    return result
  }

  result.push(1)
  if (current > 3) result.push('...')

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)
  for (let i = start; i <= end; i++) result.push(i)

  if (current < total - 2) result.push('...')
  result.push(total)

  return result
})

const from = computed(() => Math.min((props.currentPage - 1) * props.perPage + 1, props.total))
const to = computed(() => Math.min(props.currentPage * props.perPage, props.total))
</script>

<template>
  <div class="flex items-center justify-between gap-4 flex-wrap">
    <p class="text-sm text-(--color-text-muted)">
      แสดง {{ from }}-{{ to }} จาก {{ total }} รายการ
    </p>

    <div class="flex items-center gap-1">
      <!-- Previous -->
      <button
        :disabled="currentPage <= 1"
        @click="emit('change', currentPage - 1)"
        class="flex items-center justify-center w-8 h-8 rounded-(--radius-md) text-(--color-text-muted) hover:bg-(--color-bg) disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- Page numbers -->
      <template v-for="page in pages" :key="String(page) + Math.random()">
        <span
          v-if="page === '...'"
          class="flex items-center justify-center w-8 h-8 text-sm text-(--color-text-muted)"
        >
          ...
        </span>
        <button
          v-else
          @click="emit('change', page)"
          :class="[
            'flex items-center justify-center w-8 h-8 rounded-(--radius-md) text-sm transition-colors',
            page === currentPage
              ? 'bg-(--color-primary) text-white font-medium'
              : 'text-(--color-text) hover:bg-(--color-bg)'
          ]"
        >
          {{ page }}
        </button>
      </template>

      <!-- Next -->
      <button
        :disabled="currentPage >= totalPages"
        @click="emit('change', currentPage + 1)"
        class="flex items-center justify-center w-8 h-8 rounded-(--radius-md) text-(--color-text-muted) hover:bg-(--color-bg) disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </div>
</template>
