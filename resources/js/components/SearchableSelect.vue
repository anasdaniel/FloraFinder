<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from '@headlessui/vue'
import { Check, ChevronDown, Search } from 'lucide-vue-next'

interface Props {
  modelValue: string
  options: string[]
  placeholder?: string
  label?: string
  icon?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue'])

const query = ref('')

const filteredOptions = computed(() =>
  query.value === ''
    ? props.options
    : props.options.filter((option) =>
        option
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query.value.toLowerCase().replace(/\s+/g, ''))
      )
)

const handleUpdate = (value: string) => {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="relative w-full">
    <Combobox :modelValue="modelValue" @update:modelValue="handleUpdate">
      <div class="relative mt-1">
        <div
          class="relative w-full cursor-default overflow-hidden rounded-xl bg-gray-50/50 dark:bg-gray-900/50 border border-transparent focus-within:border-gray-200 dark:focus-within:border-gray-700 text-left transition-all focus:ring-2 focus:ring-gray-900/5"
        >
          <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
            <component :is="icon" v-if="icon" class="h-5 w-5 text-gray-400" aria-hidden="true" />
            <Search v-else class="h-5 w-5 text-gray-400" aria-hidden="true" />
          </div>
          <ComboboxInput
            class="w-full border-none py-2.5 pl-11 pr-10 text-sm leading-5 text-gray-900 dark:text-white bg-transparent focus:ring-0"
            :displayValue="(val: any) => val || placeholder || 'Select option'"
            @change="query = $event.target.value"
            :placeholder="placeholder"
          />
          <ComboboxButton
            class="absolute inset-y-0 right-0 flex items-center pr-2"
          >
            <ChevronDown
              class="h-4 w-4 text-gray-400"
              aria-hidden="true"
            />
          </ComboboxButton>
        </div>
        <TransitionRoot
          leave="transition ease-in duration-100"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
          @after-leave="query = ''"
        >
          <ComboboxOptions
            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white dark:bg-gray-800 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm border border-gray-100 dark:border-gray-700"
          >
            <ComboboxOption
              v-if="placeholder"
              as="template"
              value=""
              v-slot="{ selected, active }"
            >
              <li
                class="relative cursor-default select-none py-2 pl-10 pr-4"
                :class="{
                  'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': active,
                  'text-gray-900 dark:text-white': !active,
                }"
              >
                <span
                  class="block truncate"
                  :class="{ 'font-medium': selected, 'font-normal': !selected }"
                >
                  {{ placeholder }}
                </span>
                <span
                  v-if="selected"
                  class="absolute inset-y-0 left-0 flex items-center pl-3 text-green-600"
                >
                  <Check class="h-4 w-4" aria-hidden="true" />
                </span>
              </li>
            </ComboboxOption>

            <div
              v-if="filteredOptions.length === 0 && query !== ''"
              class="relative cursor-default select-none py-2 px-4 text-gray-700 dark:text-gray-300"
            >
              Nothing found.
            </div>

            <ComboboxOption
              v-for="option in filteredOptions"
              as="template"
              :key="option"
              :value="option"
              v-slot="{ selected, active }"
            >
              <li
                class="relative cursor-default select-none py-2 pl-10 pr-4"
                :class="{
                  'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': active,
                  'text-gray-900 dark:text-white': !active,
                }"
              >
                <span
                  class="block truncate"
                  :class="{ 'font-medium': selected, 'font-normal': !selected }"
                >
                  {{ option }}
                </span>
                <span
                  v-if="selected"
                  class="absolute inset-y-0 left-0 flex items-center pl-3 text-green-600"
                >
                  <Check class="h-4 w-4" aria-hidden="true" />
                </span>
              </li>
            </ComboboxOption>
          </ComboboxOptions>
        </TransitionRoot>
      </div>
    </Combobox>
  </div>
</template>
