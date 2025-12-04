<script setup lang="ts">
import { ref, watch, nextTick } from "vue";
import Icon from "@/components/Icon.vue";
import type { ChatMessage } from "@/types/plant-identifier";

const props = defineProps<{
  plantName: string;
  chatMessages: ChatMessage[];
  chatInput: string;
  isChatLoading: boolean;
}>();

const emit = defineEmits<{
  'update:chatInput': [value: string];
  send: [];
}>();

const chatEndRef = ref<HTMLElement | null>(null);

// Auto-scroll to bottom when new messages arrive
watch(
  () => props.chatMessages.length,
  async () => {
    await nextTick();
    chatEndRef.value?.scrollIntoView({ behavior: 'smooth' });
  }
);

const handleInputUpdate = (event: Event) => {
  const target = event.target as HTMLInputElement;
  emit('update:chatInput', target.value);
};

const handleSend = () => {
  emit('send');
};

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    handleSend();
  }
};
</script>

<template>
  <div class="pt-8 mt-8 border-t border-gray-100 dark:border-gray-800">
    <h3
      class="flex items-center mb-4 text-lg font-bold text-gray-900 dark:text-white"
    >
      <div
        class="mr-3 rounded-lg bg-indigo-100 p-1.5 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300"
      >
        <Icon name="message-circle" class="w-5 h-5" />
      </div>
      Ask the Botanist
    </h3>
    <div
      class="overflow-hidden border border-gray-200 shadow-inner rounded-2xl bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50"
    >
      <div class="p-4 space-y-4 overflow-y-auto h-72">
        <div
          v-if="props.chatMessages.length === 0"
          class="flex flex-col items-center justify-center h-full text-center text-gray-400"
        >
          <Icon name="sparkles" class="w-8 h-8 mb-2 text-indigo-300" />
          <p class="text-sm">
            Have questions about this {{ props.plantName }}?<br />Ask
            our AI botanist anything!
          </p>
        </div>
        <div
          v-for="(msg, idx) in props.chatMessages"
          :key="idx"
          :class="[
            'flex',
            msg.role === 'user' ? 'justify-end' : 'justify-start',
          ]"
        >
          <div
            :class="[
              'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-sm',
              msg.role === 'user'
                ? 'rounded-br-none bg-indigo-600 text-white'
                : 'rounded-bl-none bg-white text-gray-800 dark:bg-gray-700 dark:text-gray-100',
            ]"
          >
            {{ msg.text }}
          </div>
        </div>
        <div v-if="props.isChatLoading" class="flex justify-start">
          <div
            class="px-4 py-3 bg-white rounded-bl-none shadow-sm rounded-2xl dark:bg-gray-700"
          >
            <div class="flex gap-1.5">
              <div
                class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-0"
              ></div>
              <div
                class="w-2 h-2 delay-150 bg-gray-400 rounded-full animate-bounce"
              ></div>
              <div
                class="w-2 h-2 delay-300 bg-gray-400 rounded-full animate-bounce"
              ></div>
            </div>
          </div>
        </div>
        <div ref="chatEndRef"></div>
      </div>
      <div
        class="p-3 bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-gray-900"
      >
        <div class="relative flex items-center gap-2">
          <input
            :value="props.chatInput"
            @input="handleInputUpdate"
            @keydown="handleKeydown"
            placeholder="Type your question..."
            class="flex-1 rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-4 pr-12 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
          <button
            @click="handleSend"
            :disabled="!props.chatInput.trim() || props.isChatLoading"
            class="absolute right-2 rounded-lg bg-indigo-600 p-1.5 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <Icon name="send" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
