<script setup lang="ts">
import { ref, watch } from 'vue'
import { formatMoney, parseMoney } from '@/utils/money'

const props = defineProps<{
  modelValue: number | string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  class?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const inputRef = ref<HTMLInputElement>()
const displayValue = ref('')

// Sync display value when modelValue changes externally
watch(
  () => props.modelValue,
  (val) => {
    // Only update if the input is not currently focused
    if (document.activeElement !== inputRef.value) {
      const num = typeof val === 'string' ? parseFloat(parseMoney(val)) || 0 : val
      displayValue.value = num > 0 ? formatMoney(num) : ''
    }
  },
  { immediate: true }
)

// On focus: convert "12,000.50" -> "12000.50" for easy editing
function onFocus() {
  const num = parseFloat(parseMoney(displayValue.value)) || 0
  displayValue.value = num > 0 ? String(num) : ''
}

// On blur: convert "12000.50" -> "12,000.50" for display
function onBlur() {
  const num = parseFloat(parseMoney(displayValue.value)) || 0
  displayValue.value = num > 0 ? formatMoney(num) : ''
  emit('update:modelValue', num)
}

function onInput(e: Event) {
  const val = (e.target as HTMLInputElement).value
  displayValue.value = val
  const num = parseFloat(parseMoney(val)) || 0
  emit('update:modelValue', num)
}
</script>

<template>
  <input
    ref="inputRef"
    type="text"
    inputmode="decimal"
    :value="displayValue"
    :placeholder="placeholder ?? '0.00'"
    :disabled="disabled"
    :class="[
      'w-full px-3 py-2 border border-(--color-border) rounded-(--radius-md) text-(--color-text)',
      'focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary)',
      'transition-colors text-sm',
      required ? 'hidden-bg-red-50' : 'bg-(--color-surface)',
      props.class
    ]"
    @focus="onFocus"
    @blur="onBlur"
    @input="onInput"
  />
</template>
