import { ref } from 'vue'
import { formatMoney, parseMoney } from '@/utils/money'

export function useMoneyInput(initialValue: number = 0) {
  const displayValue = ref(initialValue > 0 ? formatMoney(initialValue) : '')
  const rawValue = ref(initialValue)

  // On focus: convert "12,000.50" -> "12000.50" for easy editing
  function onFocus() {
    if (displayValue.value) {
      displayValue.value = parseMoney(displayValue.value)
    }
  }

  // On blur: convert "12000.50" -> "12,000.50" for display
  function onBlur() {
    const num = parseFloat(displayValue.value) || 0
    rawValue.value = num
    displayValue.value = num > 0 ? formatMoney(num) : ''
  }

  function onInput(value: string) {
    displayValue.value = value
    rawValue.value = parseFloat(parseMoney(value)) || 0
  }

  function setValue(value: number) {
    rawValue.value = value
    displayValue.value = value > 0 ? formatMoney(value) : ''
  }

  return { displayValue, rawValue, onFocus, onBlur, onInput, setValue }
}
