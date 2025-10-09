import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useApi } from '../library/api';

export interface Theme {
  id: string;
  name: string;
  title: string;
  description: string;
  version: string;
  author: string;
  thumbnail: string;
  downloadUrl: string;
  features: string[];
}

export interface OnboardingStatus {
  completed: boolean;
  hasChildTheme: boolean;
  childTheme: {
    name: string;
    slug: string;
    version: string;
    parent: string;
  } | null;
}

export const useOnboardingStore = defineStore('onboarding', () => {
  const api = useApi();

  // State
  const loading = ref(false);
  const installing = ref(false);
  const status = ref<OnboardingStatus | null>(null);
  const availableThemes = ref<Theme[]>([]);
  const selectedThemeId = ref<string | null>(null);
  const error = ref<string | null>(null);

  // Getters
  const isCompleted = computed(() => status.value?.completed || false);
  const hasChildTheme = computed(() => status.value?.hasChildTheme || false);
  const selectedTheme = computed(() =>
    availableThemes.value.find(theme => theme.id === selectedThemeId.value) || null
  );

  // Actions
  async function fetchStatus() {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get('/onboarding/status');
      status.value = response.data.data;

      return status.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch onboarding status';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchThemes() {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get('/onboarding/themes');
      availableThemes.value = response.data.data;

      return availableThemes.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch themes';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function installTheme(themeId: string) {
    try {
      installing.value = true;
      error.value = null;

      const response = await api.post('/onboarding/install-theme', {
        themeId,
      });

      if (response.data.success) {
        // Update status after installation
        await fetchStatus();
        return response.data.data;
      } else {
        throw new Error(response.data.message || 'Installation failed');
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to install theme';
      throw err;
    } finally {
      installing.value = false;
    }
  }

  async function complete() {
    try {
      error.value = null;

      const response = await api.post('/onboarding/complete');

      if (response.data.success) {
        // Update local status
        if (status.value) {
          status.value.completed = true;
        }
      }

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to complete onboarding';
      throw err;
    }
  }

  async function reset() {
    try {
      error.value = null;

      const response = await api.post('/onboarding/reset');

      if (response.data.success) {
        // Clear local state
        status.value = null;
        selectedThemeId.value = null;
        await fetchStatus();
      }

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to reset onboarding';
      throw err;
    }
  }

  function selectTheme(themeId: string) {
    selectedThemeId.value = themeId;
  }

  function clearError() {
    error.value = null;
  }

  return {
    // State
    loading,
    installing,
    status,
    availableThemes,
    selectedThemeId,
    error,

    // Getters
    isCompleted,
    hasChildTheme,
    selectedTheme,

    // Actions
    fetchStatus,
    fetchThemes,
    installTheme,
    complete,
    reset,
    selectTheme,
    clearError,
  };
});
