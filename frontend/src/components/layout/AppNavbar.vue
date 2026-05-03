<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const emit = defineEmits<{
  'toggle-sidebar': []
}>()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const pageTitle = computed(() => (route.meta.title as string) || 'Mini POS')

const userInitial = computed(() =>
  authStore.user?.name?.charAt(0)?.toUpperCase() || '?'
)

async function logout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header class="h-16 bg-(--color-surface) border-b border-(--color-border) flex items-center px-4 gap-4 flex-shrink-0">
    <!-- Hamburger button (mobile only) -->
    <button
      @click="emit('toggle-sidebar')"
      class="lg:hidden p-2 rounded-(--radius-md) text-(--color-text-muted) hover:bg-(--color-bg) hover:text-(--color-text) transition-colors"
      aria-label="Toggle sidebar"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Page title -->
    <h1 class="flex-1 font-semibold text-(--color-text) text-base">{{ pageTitle }}</h1>

    <!-- Right side: user info + logout -->
    <div class="flex items-center gap-3">
      <!-- User name and role (hidden on small screens) -->
      <div class="text-right hidden sm:block">
        <p class="text-sm font-medium text-(--color-text) leading-none">{{ authStore.user?.name }}</p>
        <p class="text-xs text-(--color-text-muted) mt-0.5">
          {{ authStore.user?.role === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน' }}
        </p>
      </div>

      <!-- Avatar -->
      <div class="w-8 h-8 rounded-full bg-(--color-primary-light) border border-(--color-border) flex items-center justify-center overflow-hidden flex-shrink-0">
        <img
          v-if="authStore.user?.image"
          :src="authStore.user.image"
          :alt="authStore.user.name"
          class="w-full h-full object-cover"
        />
        <span v-else class="text-(--color-primary) text-sm font-semibold">{{ userInitial }}</span>
      </div>

      <!-- Logout button -->
      <button
        @click="logout"
        class="p-2 rounded-(--radius-md) text-(--color-text-muted) hover:bg-(--color-error-light) hover:text-(--color-error) transition-colors"
        title="ออกจากระบบ"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
      </button>
    </div>
  </header>
</template>
