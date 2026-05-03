<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useAlert } from '@/composables/useAlert'
import AppButton from '@/components/ui/AppButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const alert = useAlert()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)

async function handleLogin() {
  if (!username.value.trim() || !password.value) {
    alert.error('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน')
    return
  }

  loading.value = true
  try {
    await authStore.login(username.value.trim(), password.value)
    router.push('/dashboard')
  } catch (err: unknown) {
    const message =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
    alert.error(message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="w-full max-w-sm">
    <!-- Logo / Title -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-(--color-primary) rounded-(--radius-lg) mb-4 shadow-md">
        <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-(--color-text)">Mini POS</h1>
      <p class="text-(--color-text-muted) text-sm mt-1">ลงชื่อเข้าใช้งาน</p>
    </div>

    <!-- Login card -->
    <div class="bg-(--color-surface) rounded-(--radius-lg) shadow-sm border border-(--color-border) p-6">
      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Username -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            ชื่อผู้ใช้ <span class="text-(--color-error)">*</span>
          </label>
          <input
            v-model="username"
            type="text"
            required
            placeholder="กรอกชื่อผู้ใช้"
            autocomplete="username"
            class="w-full px-3 py-2.5 border border-(--color-border) rounded-(--radius-md) bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
          />
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-(--color-text) mb-1.5">
            รหัสผ่าน <span class="text-(--color-error)">*</span>
          </label>
          <div class="relative">
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="กรอกรหัสผ่าน"
              autocomplete="current-password"
              class="w-full px-3 py-2.5 pr-10 border border-(--color-border) rounded-(--radius-md) bg-red-50 text-(--color-text) text-sm focus:outline-none focus:ring-2 focus:ring-(--color-primary)/30 focus:border-(--color-primary) transition-colors"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-(--color-text-muted) hover:text-(--color-text) transition-colors"
              :aria-label="showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
            >
              <!-- Eye-slash (hide) -->
              <svg v-if="showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
              </svg>
              <!-- Eye (show) -->
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
        </div>

        <AppButton type="submit" variant="primary" size="lg" :loading="loading" class="w-full mt-2">
          เข้าสู่ระบบ
        </AppButton>
      </form>
    </div>
  </div>
</template>
