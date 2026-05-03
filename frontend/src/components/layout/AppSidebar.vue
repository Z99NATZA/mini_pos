<script setup lang="ts">
import { computed } from "vue";
import { RouterLink } from "vue-router";
import { useAuthStore } from "@/stores/auth.store";

const emit = defineEmits<{
  close: [];
}>();

const authStore = useAuthStore();

const iconPaths: Record<string, string> = {
  dashboard:
    "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
  pos: "M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z",
  orders:
    "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",
  products: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
  sizes:
    "M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4",
  types:
    "M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z",
  toppings:
    "M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z",
  users:
    "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
};

const allNavItems = [
  { path: "/dashboard", name: "หน้าแรก", adminOnly: false, icon: "dashboard" },
  { path: "/pos", name: "ขาย", adminOnly: false, icon: "pos" },
  { path: "/orders", name: "รายการขาย", adminOnly: false, icon: "orders" },
  { path: "/products", name: "สินค้า", adminOnly: true, icon: "products" },
  { path: "/sizes", name: "ไซต์", adminOnly: true, icon: "sizes" },
  { path: "/types", name: "ประเภท", adminOnly: true, icon: "types" },
  { path: "/toppings", name: "ท็อปปิ้ง", adminOnly: true, icon: "toppings" },
  { path: "/users", name: "ผู้ใช้งาน", adminOnly: true, icon: "users" },
];

const navItems = computed(() =>
  allNavItems.filter((item) => !item.adminOnly || authStore.isAdmin),
);

const userInitial = computed(
  () => authStore.user?.name?.charAt(0)?.toUpperCase() || "?",
);
</script>

<template>
  <aside
    class="w-64 h-full bg-(--color-sidebar-bg) border-r border-(--color-border) flex flex-col flex-shrink-0 select-none"
  >
    <!-- Logo -->
    <div
      class="h-16 flex items-center px-6 border-b border-(--color-border) flex-shrink-0"
    >
      <div class="flex items-center gap-2.5">
        <div
          class="w-8 h-8 bg-(--color-primary) rounded-(--radius-md) flex items-center justify-center shadow-sm"
        >
          <svg
            class="w-4 h-4 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
            />
          </svg>
        </div>
        <span class="font-bold text-lg tracking-wide text-(--color-text)"
          >Mini POS</span
        >
      </div>
    </div>

    <!-- Navigation links -->
    <nav class="flex-1 py-3 overflow-y-auto space-y-0.5 px-2">
      <RouterLink
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        @click="emit('close')"
        active-class="bg-(--color-sidebar-active-bg) !text-(--color-sidebar-text-active) shadow-sm"
        :class="[
          'flex items-center gap-3 px-4 py-2.5 rounded-(--radius-md) text-sm font-medium transition-colors',
          'text-(--color-sidebar-text) hover:bg-(--color-sidebar-hover-bg) hover:text-(--color-sidebar-hover-text)',
        ]"
      >
        <svg
          class="w-[18px] h-[18px] flex-shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.7"
            :d="iconPaths[item.icon]"
          />
        </svg>
        <span>{{ item.name }}</span>
      </RouterLink>
    </nav>

    <!-- User info at bottom -->
    <div class="px-4 py-4 border-t border-(--color-border) flex-shrink-0">
      <div class="flex items-center gap-3">
        <div
          class="w-8 h-8 rounded-full bg-(--color-primary-light) border border-(--color-border) flex items-center justify-center flex-shrink-0"
        >
          <span class="text-xs font-bold text-(--color-primary)">{{
            userInitial
          }}</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-semibold text-(--color-text) truncate">
            {{ authStore.user?.name }}
          </p>
          <p class="text-xs text-(--color-text-muted)">
            {{ authStore.user?.role === "admin" ? "ผู้ดูแลระบบ" : "พนักงาน" }}
          </p>
        </div>
      </div>
    </div>
  </aside>
</template>
