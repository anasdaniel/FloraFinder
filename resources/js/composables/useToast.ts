import { ref } from 'vue';

export interface Toast {
  id: string;
  title?: string;
  description?: string;
  variant?: 'default' | 'success' | 'destructive';
  duration?: number;
}

// Module-scoped singleton state so all callers share the same store
const toasts = ref<Toast[]>([]);

// Generate a unique ID for each toast
const generateId = () => Math.random().toString(36).substring(2, 9);

// Dismiss a toast by id
const dismiss = (id: string) => {
  toasts.value = toasts.value.filter((t) => t.id !== id);
};

// Dismiss all toasts
const dismissAll = () => {
  toasts.value = [];
};

// Add a new toast
const toast = (props: Omit<Toast, 'id'>) => {
  const id = generateId();
  const duration = props.duration ?? 5000;

  const newToast: Toast = {
    id,
    title: props.title,
    description: props.description,
    variant: props.variant ?? 'default',
    duration,
  };

  toasts.value = [...toasts.value, newToast];

  // Automatically dismiss the toast after duration
  setTimeout(() => {
    dismiss(id);
  }, duration);

  return id;
};

export function useToast() {
  return {
    toast,
    toasts,
    dismiss,
    dismissAll,
  };
}
