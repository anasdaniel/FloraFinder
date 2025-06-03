<template>
  <div class="flex items-center justify-center space-x-2">
    <button
      @click="$emit('page-changed', currentPage - 1)"
      :disabled="currentPage <= 1"
      class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700"
    >
      Previous
    </button>

    <span class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
      Page {{ currentPage }} of {{ totalPages }}
    </span>

    <button
      @click="$emit('page-changed', currentPage + 1)"
      :disabled="currentPage >= totalPages"
      class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700"
    >
      Next
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  currentPage: number;
  totalPages: number;
}>();

defineEmits<{
  'page-changed': [page: number];
}>();
</script>
