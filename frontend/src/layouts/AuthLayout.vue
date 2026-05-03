<script setup lang="ts">
import { RouterView } from "vue-router";
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center p-4"
    style="
      background: linear-gradient(
        160deg,
        #a8d8f0 0%,
        #c5e8f7 40%,
        #ddf0fb 100%
      );
    "
  >
    <RouterView />
  </div>
</template>
