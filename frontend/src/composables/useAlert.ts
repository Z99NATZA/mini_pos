import { useAlertStore } from '@/stores/alert.store'

export function useAlert() {
  const alertStore = useAlertStore()

  return {
    success: (message: string) => alertStore.success(message),
    error: (message: string) => alertStore.error(message),
    warning: (message: string) => alertStore.warning(message),
    confirm: (message: string, onConfirm: () => void, title?: string) =>
      alertStore.confirm(message, onConfirm, title)
  }
}
