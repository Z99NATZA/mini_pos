<script setup lang="ts">
import { computed } from 'vue'
import { useAlertStore } from '@/stores/alert.store'
import AppModal from './AppModal.vue'
import AppButton from './AppButton.vue'

const alertStore = useAlertStore()

// Icon + color config per alert type
const config = computed(() => {
  switch (alertStore.options.type) {
    case 'success':
      return { iconBg: 'bg-(--color-success-light)', iconColor: 'text-(--color-success)' }
    case 'error':
      return { iconBg: 'bg-(--color-error-light)', iconColor: 'text-(--color-error)' }
    case 'warning':
      return { iconBg: 'bg-(--color-warning-light)', iconColor: 'text-(--color-warning)' }
    default:
      return { iconBg: 'bg-(--color-primary-light)', iconColor: 'text-(--color-primary)' }
  }
})

const title = computed(() => {
  if (alertStore.options.title) return alertStore.options.title
  switch (alertStore.options.type) {
    case 'success': return 'สำเร็จ'
    case 'error': return 'เกิดข้อผิดพลาด'
    case 'warning': return 'คำเตือน'
    case 'confirm': return 'ยืนยัน'
    default: return ''
  }
})

function onOk() {
  alertStore.hide()
}

function onConfirm() {
  alertStore.options.onConfirm?.()
  alertStore.hide()
}

function onCancel() {
  alertStore.hide()
}
</script>

<template>
  <AppModal
    :model-value="alertStore.visible"
    :title="title"
    size="sm"
    @update:model-value="alertStore.hide"
  >
    <div class="flex flex-col items-center text-center gap-4 py-2">
      <!-- Icon -->
      <div :class="['w-14 h-14 rounded-full flex items-center justify-center flex-shrink-0', config.iconBg]">
        <!-- Success check -->
        <svg
          v-if="alertStore.options.type === 'success'"
          :class="['w-7 h-7', config.iconColor]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <!-- Error X -->
        <svg
          v-else-if="alertStore.options.type === 'error'"
          :class="['w-7 h-7', config.iconColor]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <!-- Warning triangle -->
        <svg
          v-else-if="alertStore.options.type === 'warning'"
          :class="['w-7 h-7', config.iconColor]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <!-- Confirm question mark -->
        <svg
          v-else
          :class="['w-7 h-7', config.iconColor]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>

      <!-- Message -->
      <p class="text-(--color-text) text-sm leading-relaxed max-w-xs">
        {{ alertStore.options.message }}
      </p>
    </div>

    <template #footer>
      <!-- Confirm type: two buttons -->
      <template v-if="alertStore.options.type === 'confirm'">
        <AppButton variant="secondary" @click="onCancel">ยกเลิก</AppButton>
        <AppButton variant="primary" @click="onConfirm">ยืนยัน</AppButton>
      </template>
      <!-- Other types: OK button -->
      <template v-else>
        <AppButton variant="primary" @click="onOk">ตกลง</AppButton>
      </template>
    </template>
  </AppModal>
</template>
