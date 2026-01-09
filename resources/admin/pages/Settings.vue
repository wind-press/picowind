<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useApi } from '../library/api';

const api = useApi();

const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

// Settings
const daisyuiEnabled = ref(false);

onMounted(async () => {
  await loadSettings();
});

async function loadSettings() {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await api.get('/config');
    
    if (response.data) {
      daisyuiEnabled.value = response.data.features?.daisyui || false;
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to load settings';
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  try {
    saving.value = true;
    error.value = null;
    success.value = null;
    
    const settings = {
      features: {
        daisyui: daisyuiEnabled.value,
      },
    };
    
    await api.post('/config', settings);
    
    success.value = 'Settings saved successfully!';
    
    // Clear success message after 3 seconds
    setTimeout(() => {
      success.value = null;
    }, 3000);
  } catch (err: any) {
    error.value = err.message || 'Failed to save settings';
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="p-8 max-w-4xl">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Theme Settings</h1>
      <p class="text-gray-600">Configure your Picowind theme options</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-600">Loading settings...</p>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-red-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        <div>
          <h3 class="text-sm font-medium text-red-800">Error</h3>
          <p class="text-sm text-red-700 mt-1">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Success Alert -->
    <div v-else-if="success" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <div>
          <h3 class="text-sm font-medium text-green-800">Success</h3>
          <p class="text-sm text-green-700 mt-1">{{ success }}</p>
        </div>
      </div>
    </div>

    <!-- Settings Form -->
    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200">
      <!-- Features Section -->
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Features</h2>
        
        <!-- DaisyUI Toggle -->
        <div class="flex items-start justify-between py-4">
          <div class="flex-1 pr-6">
            <h3 class="text-base font-medium text-gray-900 mb-1">DaisyUI Components</h3>
            <p class="text-sm text-gray-600">
              Enable DaisyUI component library for pre-styled UI components. When enabled, 
              templates will use DaisyUI classes and components for forms, buttons, cards, and more.
            </p>
            <p class="text-xs text-gray-500 mt-2">
              Note: Requires WindPress plugin with DaisyUI configuration to be active.
            </p>
          </div>
          <div class="flex-shrink-0">
            <button
              @click="daisyuiEnabled = !daisyuiEnabled"
              type="button"
              :class="daisyuiEnabled ? 'bg-blue-600' : 'bg-gray-200'"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              <span
                :class="daisyuiEnabled ? 'translate-x-5' : 'translate-x-0'"
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
              ></span>
            </button>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="px-6 py-4 bg-gray-50 flex justify-end">
        <button
          @click="saveSettings"
          :disabled="saving"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </div>
  </div>
</template>
