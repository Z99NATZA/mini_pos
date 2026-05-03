import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { AlertOptions } from '@/types'

export const useAlertStore = defineStore('alert', () => {
  const visible = ref(false)
  const options = ref<AlertOptions>({
    type: 'success',
    message: ''
  })

  function show(opts: AlertOptions) {
    options.value = opts
    visible.value = true
  }

  function success(message: string) {
    show({ type: 'success', message })
  }

  function error(message: string) {
    show({ type: 'error', message })
  }

  function warning(message: string) {
    show({ type: 'warning', message })
  }

  function confirm(message: string, onConfirm: () => void, title?: string) {
    show({ type: 'confirm', message, onConfirm, title })
  }

  function hide() {
    visible.value = false
  }

  return { visible, options, show, success, error, warning, confirm, hide }
})
