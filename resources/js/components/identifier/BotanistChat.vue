<script setup lang="ts">
import { ref, watch, nextTick, computed } from "vue";
import Icon from "@/components/Icon.vue";
import type { ChatMessage } from "@/types/plant-identifier";

const props = defineProps<{
  plantName: string;
  chatMessages: ChatMessage[];
  chatInput: string;
  isChatLoading: boolean;
  isThreatenedSpecies?: boolean;
  iucnCategory?: string | null;
  hasCareData?: boolean;
}>();

const emit = defineEmits<{
  'update:chatInput': [value: string];
  send: [];
}>();

const chatEndRef = ref<HTMLElement | null>(null);

// Default care-focused questions
const careQuestions = [
  "How often should I water this?",
  "Does it need direct sunlight?",
  "Is this plant toxic to pets?",
  "What type of soil is best?",
];

// Conservation-focused questions for threatened species
const conservationQuestions = [
  "Why is this species endangered?",
  "Where is its natural habitat?",
  "What conservation efforts exist?",
  "How can I help protect it?",
];

// Questions for species with no care data (rare/uncultivatable)
const ecologyQuestions = [
  "What makes this species unique?",
  "Where can I see this in the wild?",
  "What is its ecological role?",
  "Is this plant edible or medicinal?",
];

// Dynamically select questions based on species status
const suggestedQuestions = computed(() => {
  if (props.isThreatenedSpecies) {
    return conservationQuestions;
  }
  if (props.hasCareData === false) {
    return ecologyQuestions;
  }
  return careQuestions;
});

// Dynamic header text based on species status
const headerText = computed(() => {
  if (props.isThreatenedSpecies) {
    return `Learn about ${props.plantName}'s conservation`;
  }
  return `Curious about ${props.plantName}?`;
});

// Dynamic subtitle based on species status
const subtitleText = computed(() => {
  if (props.isThreatenedSpecies) {
    return 'Ask about conservation status, habitat, or protection efforts.';
  }
  if (props.hasCareData === false) {
    return 'Ask about this species\' ecology, habitat, or unique characteristics.';
  }
  return 'Select a question below or type your own.';
});

// Dynamic placeholder text
const placeholderText = computed(() => {
  if (props.isThreatenedSpecies) {
    return 'Ask about conservation, habitat, threats...';
  }
  if (props.hasCareData === false) {
    return 'Ask about ecology, habitat, characteristics...';
  }
  return 'Ask about care tips, origin, etc...';
});

const handleSuggestionClick = (question: string) => {
  emit('update:chatInput', question);
  // Optional: Auto-send or just populate
  // emit('send'); // Uncomment if you want immediate send
};

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
    <div class="flex items-center justify-between mb-4">
      <h3 class="flex items-center text-lg font-bold text-gray-900 dark:text-white">
        <div
          class="mr-3 rounded-full p-2"
          :class="props.isThreatenedSpecies
            ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300'
            : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300'"
        >
          <Icon :name="props.isThreatenedSpecies ? 'shield-question' : 'message-circle'" class="w-5 h-5" />
        </div>
        {{ props.isThreatenedSpecies ? 'Ask About Conservation' : 'Ask the AI Botanist' }}
      </h3>
      <span class="flex items-center gap-2 px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 rounded-full border border-green-100 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
        <span class="relative flex w-2 h-2">
          <span class="absolute inline-flex w-full h-full bg-green-400 rounded-full opacity-75 animate-ping"></span>
          <span class="relative inline-flex w-2 h-2 bg-green-500 rounded-full"></span>
        </span>
        Online
      </span>
    </div>

    <div
      class="flex flex-col overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl h-[32rem] dark:bg-gray-900 dark:border-gray-700"
    >
      <!-- Chat Area -->
      <div class="flex-1 p-4 overflow-y-auto bg-gray-50/50 dark:bg-gray-900/50">
        <!-- Empty State with Quick Chips -->
        <div
          v-if="props.chatMessages.length === 0"
          class="flex flex-col items-center justify-center h-full space-y-6"
        >
          <div
            class="p-4 rounded-full shadow-sm"
            :class="props.isThreatenedSpecies
              ? 'bg-amber-100 dark:bg-amber-900/30'
              : 'bg-white dark:bg-gray-800'"
          >
            <Icon
              :name="props.isThreatenedSpecies ? 'shield-alert' : 'sprout'"
              :class="props.isThreatenedSpecies
                ? 'w-10 h-10 text-amber-600 dark:text-amber-400'
                : 'w-10 h-10 text-indigo-500'"
            />
          </div>
          <div class="text-center">
            <h4 class="text-base font-semibold text-gray-900 dark:text-white">
              {{ headerText }}
            </h4>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ subtitleText }}
            </p>
          </div>
          <div class="grid grid-cols-1 gap-2 w-full max-w-sm sm:grid-cols-2">
            <button
              v-for="question in suggestedQuestions"
              :key="question"
              @click="handleSuggestionClick(question)"
              class="px-4 py-3 text-sm text-left transition-all border shadow-sm rounded-xl hover:shadow-md"
              :class="props.isThreatenedSpecies
                ? 'text-amber-800 bg-amber-50 border-amber-200 hover:border-amber-400 hover:text-amber-900 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-200 dark:hover:border-amber-600'
                : 'text-gray-700 bg-white border-gray-200 hover:border-indigo-300 hover:text-indigo-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:border-indigo-500 dark:hover:text-indigo-400'"
            >
              {{ question }}
            </button>
          </div>
        </div>

        <!-- Message List -->
        <div v-else class="space-y-6">
          <div
            v-for="(msg, idx) in props.chatMessages"
            :key="idx"
            :class="[
              'flex gap-3',
              msg.role === 'user' ? 'justify-end' : 'justify-start',
            ]"
          >
            <!-- Bot Avatar -->
            <div
              v-if="msg.role !== 'user'"
              class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300 mt-1"
            >
              <Icon name="bot" class="w-4 h-4" />
            </div>

            <!-- Message Bubble -->
            <div
              :class="[
                'max-w-[85%] rounded-2xl px-5 py-3.5 text-sm leading-relaxed shadow-sm',
                msg.role === 'user'
                  ? 'bg-indigo-600 text-white rounded-br-sm'
                  : 'bg-white text-gray-800 border border-gray-100 rounded-bl-sm dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700',
              ]"
            >
              {{ msg.text }}
            </div>

            <!-- User Avatar (Optional/Placeholder) -->
            <!-- <div v-if="msg.role === 'user'" class="w-8 h-8 ..."></div> -->
          </div>

          <!-- Typing Indicator -->
          <div v-if="props.isChatLoading" class="flex gap-3 justify-start">
            <div
              class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300 mt-1"
            >
              <Icon name="bot" class="w-4 h-4" />
            </div>
            <div
              class="px-5 py-4 bg-white border border-gray-100 rounded-2xl rounded-bl-sm shadow-sm dark:bg-gray-800 dark:border-gray-700"
            >
              <div class="flex gap-1.5">
                <div
                  class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce delay-0"
                ></div>
                <div
                  class="w-1.5 h-1.5 delay-150 bg-gray-400 rounded-full animate-bounce"
                ></div>
                <div
                  class="w-1.5 h-1.5 delay-300 bg-gray-400 rounded-full animate-bounce"
                ></div>
              </div>
            </div>
          </div>
          <div ref="chatEndRef"></div>
        </div>
      </div>

      <!-- Input Area -->
      <div
        class="p-4 bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-gray-900"
      >
        <div class="relative flex items-center gap-3">
          <input
            :value="props.chatInput"
            @input="handleInputUpdate"
            @keydown="handleKeydown"
            :placeholder="placeholderText"
            class="flex-1 rounded-xl border-gray-200 bg-gray-50 py-3 pl-4 pr-12 text-sm transition-shadow focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
          <button
            @click="handleSend"
            :disabled="!props.chatInput.trim() || props.isChatLoading"
            class="absolute right-2 rounded-lg bg-indigo-600 p-2 text-white transition-all hover:bg-indigo-700 hover:shadow disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
          >
            <Icon name="send" class="w-4 h-4" />
          </button>
        </div>
        <p class="mt-2 text-xs text-center text-gray-400 dark:text-gray-500">
          AI can make mistakes. Verify important information.
        </p>
      </div>
    </div>
  </div>
</template>
