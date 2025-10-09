<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useOnboardingStore } from '../stores/onboarding';
import { storeToRefs } from 'pinia';

const router = useRouter();
const onboardingStore = useOnboardingStore();

// Destructure store state and getters
const {
  loading,
  installing,
  status,
  availableThemes,
  selectedThemeId,
  error,
  isCompleted,
  hasChildTheme,
} = storeToRefs(onboardingStore);

const currentStep = ref<'check' | 'select' | 'install' | 'complete'>('check');

onMounted(async () => {
  await checkOnboardingStatus();
});

async function checkOnboardingStatus() {
  try {
    await onboardingStore.fetchStatus();

    if (isCompleted.value) {
      currentStep.value = 'complete';
    } else if (hasChildTheme.value) {
      currentStep.value = 'complete';
      await onboardingStore.complete();
    } else {
      currentStep.value = 'select';
      await onboardingStore.fetchThemes();
    }
  } catch (err) {
    // Error is already set in the store
    console.error('Failed to check onboarding status:', err);
  }
}

async function installSelectedTheme() {
  if (!selectedThemeId.value) {
    onboardingStore.error = 'Please select a theme';
    return;
  }

  try {
    onboardingStore.clearError();
    currentStep.value = 'install';

    await onboardingStore.installTheme(selectedThemeId.value);
    await onboardingStore.complete();
    currentStep.value = 'complete';
  } catch (err) {
    // Error is already set in the store
    currentStep.value = 'select';
  }
}

function skipOnboarding() {
  router.push('/settings');
}

function goToSettings() {
  router.push('/settings');
}
</script>

<template>
  <div class="min-h-[calc(100vh-200px)] bg-gray-50 p-8">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-600">Loading...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-2xl mx-auto">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-red-800 mb-2">Error</h3>
        <p class="text-red-600">{{ error }}</p>
        <button
          @click="checkOnboardingStatus"
          class="mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded transition-colors"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- Welcome & Theme Selection -->
    <div v-else-if="currentStep === 'select'" class="max-w-5xl mx-auto">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to Picowind!</h1>
        <p class="text-xl text-gray-600">Let's get you started by selecting a child theme</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div
          v-for="theme in availableThemes"
          :key="theme.id"
          @click="onboardingStore.selectTheme(theme.id)"
          class="bg-white rounded-lg border-2 transition-all cursor-pointer hover:shadow-lg"
          :class="selectedThemeId === theme.id ? 'border-blue-600 shadow-lg' : 'border-gray-200'"
        >
          <div class="p-6">
            <!-- Thumbnail placeholder -->
            <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg h-40 mb-4 flex items-center justify-center">
              <span class="text-4xl font-bold text-white">{{ theme.name }}</span>
            </div>

            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ theme.title }}</h3>
            <p class="text-gray-600 text-sm mb-4">{{ theme.description }}</p>

            <div class="space-y-1">
              <p class="text-xs font-semibold text-gray-700 mb-2">Features:</p>
              <ul class="space-y-1">
                <li v-for="(feature, idx) in theme.features" :key="idx" class="text-xs text-gray-600 flex items-start">
                  <span class="text-blue-600 mr-2">✓</span>
                  {{ feature }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-center gap-4">
        <button
          @click="skipOnboarding"
          class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded transition-colors"
        >
          Skip for Now
        </button>
        <button
          @click="installSelectedTheme"
          :disabled="!selectedThemeId || installing"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ installing ? 'Installing...' : 'Install Selected Theme' }}
        </button>
      </div>
    </div>

    <!-- Installing State -->
    <div v-else-if="currentStep === 'install'" class="flex items-center justify-center h-96">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-6"></div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Installing Theme...</h2>
        <p class="text-gray-600">Please wait while we download and activate your selected theme</p>
      </div>
    </div>

    <!-- Completion State -->
    <div v-else-if="currentStep === 'complete'" class="max-w-2xl mx-auto text-center">
      <div class="mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
          <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">All Set!</h1>
        <p class="text-xl text-gray-600 mb-8">
          {{ hasChildTheme
            ? 'Your child theme is active and ready to use.'
            : 'Onboarding completed successfully!'
          }}
        </p>
      </div>

      <div v-if="status?.childTheme" class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">Active Child Theme</h3>
        <p class="text-blue-700">{{ status.childTheme.name }}</p>
        <p class="text-sm text-blue-600">Version {{ status.childTheme.version }}</p>
      </div>

      <button
        @click="goToSettings"
        class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
      >
        Go to Settings
      </button>
    </div>
  </div>
</template>
