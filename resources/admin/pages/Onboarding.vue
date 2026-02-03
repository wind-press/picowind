<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useOnboardingStore } from '../stores/onboarding';

const onboardingStore = useOnboardingStore();
const { status, bundledThemes, recommendedPlugins, error } = storeToRefs(onboardingStore);

const pageLoading = ref(true);
const actionState = ref<{ id: string; type: 'install' | 'activate' } | null>(null);
const pluginActionState = ref<{ slug: string; type: 'install' | 'activate' } | null>(null);
const successMessage = ref<string | null>(null);

const isBusy = computed(() => actionState.value !== null || pluginActionState.value !== null);
const hasActiveChildTheme = computed(() => Boolean(status.value?.childTheme));
const activeChildThemeName = computed(() => status.value?.childTheme?.name ?? '');

onMounted(async () => {
  try {
    await refresh();
  } finally {
    pageLoading.value = false;
  }
});

async function refresh() {
  try {
    await Promise.all([
      onboardingStore.fetchStatus(),
      onboardingStore.fetchThemes(),
      onboardingStore.fetchPlugins(),
    ]);
  } catch (err) {
    // Error is handled by the store
  }
}

function isAction(themeId: string, type: 'install' | 'activate') {
  return actionState.value?.id === themeId && actionState.value?.type === type;
}

function isPluginAction(slug: string, type: 'install' | 'activate') {
  return pluginActionState.value?.slug === slug && pluginActionState.value?.type === type;
}

async function installTheme(themeId: string) {
  successMessage.value = null;
  actionState.value = { id: themeId, type: 'install' };

  try {
    const response = await onboardingStore.installTheme(themeId);
    successMessage.value = response?.message || 'Theme installed successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    actionState.value = null;
  }
}

async function activateTheme(themeSlug: string, themeId: string) {
  if (!themeSlug) {
    return;
  }

  successMessage.value = null;
  actionState.value = { id: themeId, type: 'activate' };

  try {
    const response = await onboardingStore.activateTheme(themeSlug);
    successMessage.value = response?.message || 'Theme activated successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    actionState.value = null;
  }
}

async function installPlugin(slug: string) {
  successMessage.value = null;
  pluginActionState.value = { slug, type: 'install' };

  try {
    const response = await onboardingStore.installPlugin(slug);
    successMessage.value = response?.message || 'Plugin installed successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    pluginActionState.value = null;
  }
}

async function activatePlugin(slug: string) {
  successMessage.value = null;
  pluginActionState.value = { slug, type: 'activate' };

  try {
    const response = await onboardingStore.activatePlugin(slug);
    successMessage.value = response?.message || 'Plugin activated successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    pluginActionState.value = null;
  }
}
</script>

<template>
  <div class="min-h-[calc(100vh-200px)] bg-slate-50 p-8">
    <div class="mx-auto flex max-w-6xl flex-col gap-10">
      <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Onboarding</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900">Get started with Picowind</h1>
            <p class="mt-2 text-sm text-slate-600">
              Choose a bundled child theme and add the plugins you need to complete your setup.
            </p>
          </div>
        </div>

        <div
          v-if="successMessage"
          class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
        >
          {{ successMessage }}
        </div>

        <div v-if="error" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {{ error }}
        </div>

      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-slate-900">Bundled child themes</h2>
            <p class="text-sm text-slate-600">
              Install a bundled child theme or switch to one you already have installed.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">{{ bundledThemes.length }} bundles</span>
            <div
              v-if="hasActiveChildTheme"
              class="rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-xs font-semibold text-green-700"
            >
              Active child theme: {{ activeChildThemeName }}
            </div>
          </div>
        </div>

        <div v-if="pageLoading" class="mt-8 flex items-center justify-center py-12">
          <div class="text-center">
            <div class="inline-block h-12 w-12 animate-spin rounded-full border-b-2 border-slate-500"></div>
            <p class="mt-3 text-sm text-slate-500">Loading bundled themes...</p>
          </div>
        </div>

        <div
          v-else-if="bundledThemes.length === 0 && !error"
          class="mt-8 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600"
        >
          No bundled child themes were found in <span class="font-mono text-slate-700">@child-theme/</span>.
        </div>

        <div v-else-if="bundledThemes.length > 0" class="mt-8 grid gap-6 md:grid-cols-2">
          <article
            v-for="theme in bundledThemes"
            :key="theme.id"
            class="flex h-full flex-col justify-between rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-slate-300"
          >
            <div>
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-lg font-semibold text-slate-900">{{ theme.name }}</h3>
                  <p class="mt-2 text-sm text-slate-600">
                    {{ theme.description || 'A bundled child theme for Picowind.' }}
                  </p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                  <span
                    v-if="theme.active"
                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                  >
                    Active
                  </span>
                  <span
                    v-else-if="theme.installed"
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                  >
                    Installed
                  </span>
                  <span v-else class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                    Not installed
                  </span>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500">
                <span v-if="theme.version">Version {{ theme.version }}</span>
                <span v-if="theme.author">By {{ theme.author }}</span>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
              <button
                v-if="!theme.installed"
                @click="installTheme(theme.id)"
                :disabled="isBusy"
                class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
              >
                {{ isAction(theme.id, 'install') ? 'Installing...' : 'Install' }}
              </button>
              <button
                v-else-if="theme.active"
                disabled
                class="rounded-lg bg-green-100 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-green-700"
              >
                Active
              </button>
              <button
                v-else
                @click="activateTheme(theme.installedSlug || '', theme.id)"
                :disabled="!theme.installedSlug || isBusy"
                class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:bg-blue-300"
              >
                {{ isAction(theme.id, 'activate') ? 'Switching...' : 'Activate' }}
              </button>
            </div>
          </article>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-slate-900">Recommended plugins</h2>
            <p class="text-sm text-slate-600">These plugins pair perfectly with Picowind.</p>
          </div>
        </div>

        <div v-if="pageLoading" class="mt-8 flex items-center justify-center py-12">
          <div class="text-center">
            <div class="inline-block h-12 w-12 animate-spin rounded-full border-b-2 border-slate-500"></div>
            <p class="mt-3 text-sm text-slate-500">Loading recommended plugins...</p>
          </div>
        </div>

        <div v-else class="mt-6 grid gap-5 md:grid-cols-3">
          <article
            v-for="plugin in recommendedPlugins"
            :key="plugin.id"
            class="flex h-full flex-col justify-between rounded-xl border border-slate-200 bg-slate-50 p-5"
          >
            <div>
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-lg font-semibold text-slate-900">{{ plugin.name }}</h3>
                  <p class="mt-2 text-sm text-slate-600">{{ plugin.description }}</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                  <span
                    v-if="plugin.active"
                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                  >
                    Active
                  </span>
                  <span
                    v-else-if="plugin.installed"
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                  >
                    Installed
                  </span>
                  <span
                    v-else-if="plugin.source === 'wporg'"
                    class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                  >
                    Not installed
                  </span>
                  <span v-else class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">
                    Premium
                  </span>
                </div>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
              <p class="text-xs text-slate-500">
                {{ plugin.source === 'wporg' ? 'WordPress.org plugin' : 'Premium plugin' }}
              </p>

              <div class="flex flex-wrap gap-2">
                <a
                  v-if="plugin.source === 'external'"
                  :href="plugin.url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-slate-800"
                >
                  Visit {{ plugin.name }}
                </a>
                <button
                  v-else-if="!plugin.installed"
                  @click="installPlugin(plugin.slug)"
                  :disabled="isBusy"
                  class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                >
                  {{ isPluginAction(plugin.slug, 'install') ? 'Installing...' : 'Install' }}
                </button>
                <button
                  v-else-if="!plugin.active"
                  @click="activatePlugin(plugin.slug)"
                  :disabled="isBusy"
                  class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:bg-blue-300"
                >
                  {{ isPluginAction(plugin.slug, 'activate') ? 'Activating...' : 'Activate' }}
                </button>
                <button
                  v-else
                  disabled
                  class="rounded-lg bg-green-100 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-green-700"
                >
                  Active
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>
