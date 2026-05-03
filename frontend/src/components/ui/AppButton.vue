<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
}>(), {
  variant: 'primary',
  size: 'md',
  loading: false,
  disabled: false,
  type: 'button'
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center font-medium transition-colors rounded-(--radius-md)',
      'focus:outline-none focus:ring-2 focus:ring-offset-1',
      'disabled:opacity-60 disabled:cursor-not-allowed',
      // Sizes
      size === 'sm' && 'px-3 py-1.5 text-xs gap-1.5',
      size === 'md' && 'px-4 py-2 text-sm gap-2',
      size === 'lg' && 'px-5 py-2.5 text-sm gap-2',
      // Variants
      variant === 'primary' && 'bg-(--color-primary) text-white hover:bg-(--color-primary-hover) focus:ring-(--color-primary)/30',
      variant === 'secondary' && 'bg-(--color-bg) text-(--color-text) border border-(--color-border) hover:bg-(--color-border)/60 focus:ring-(--color-border)',
      variant === 'danger' && 'bg-(--color-error) text-white hover:bg-red-700 focus:ring-(--color-error)/30',
      variant === 'ghost' && 'bg-transparent text-(--color-text-muted) hover:bg-(--color-bg) hover:text-(--color-text) focus:ring-(--color-border)',
    ]"
  >
    <!-- Loading spinner -->
    <svg
      v-if="loading"
      class="animate-spin w-4 h-4 flex-shrink-0"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot />
  </button>
</template>
