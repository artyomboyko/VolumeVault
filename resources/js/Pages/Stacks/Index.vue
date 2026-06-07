<script setup lang="ts">
import ActionIcon from '@/Components/ActionIcon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from '@/i18n';
import { formatBytes } from '@/Composables/useFormatBytes';
import { matchesSearch, readFiltersFromUrl, useListFilters, useUrlFilters } from '@/Composables/useListFilters';

const props = defineProps<{ stacks: any[] }>();

const page = usePage();
const can = page.props.can as { runDockerActions?: boolean };
const { t, formatDate } = useI18n();
const search = ref('');
const backupFilter = ref('');
const filtersVisible = ref(false);

readFiltersFromUrl({ search, backup_status: backupFilter });
useUrlFilters({ search, backup_status: backupFilter }, { debounce: 300 });

const { hasActiveFilters, resetFilters } = useListFilters([search, backupFilter]);
const { activeFilterCount: activeAdvancedFilterCount } = useListFilters([backupFilter]);

const filteredStacks = computed(() => props.stacks.filter((stack) => {
    const searchableVolumes = stack.volumes.map((volume: any) => volume.name).join(' ');

    return matchesSearch([stack.name || t('No stack'), searchableVolumes], search.value)
        && (!backupFilter.value || stack.volumes.some((volume: any) => volume.backup_state === backupFilter.value));
}));

const backupStateLabel = (state: string) => ({
    backed_up: t('Backed up'),
    configured: t('Pending backup'),
    unprotected: t('Unprotected'),
}[state] || t('Unknown'));

const backupStateClass = (state: string) => ({
    backed_up: 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100',
    configured: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
    unprotected: 'border-rose-300/30 bg-rose-300/10 text-rose-100',
}[state] || 'border-slate-300/20 bg-slate-300/10 text-slate-200');

const stackConfigurationLabel = (state: string) => ({
    configured: t('Stack configured'),
    partially_configured: t('Partially configured'),
    not_configured: t('Not configured'),
}[state] || t('Unknown'));

const stackConfigurationClass = (state: string) => ({
    configured: 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100',
    partially_configured: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
    not_configured: 'border-rose-300/30 bg-rose-300/10 text-rose-100',
}[state] || 'border-slate-300/20 bg-slate-300/10 text-slate-200');

const jobsHref = (volumeName: string) => `/backup-jobs?search=${encodeURIComponent(volumeName)}`;
</script>

<template>
    <Head :title="t('Stacks')" />
    <AppLayout :title="t('Docker stacks')" :subtitle="t('Group discovered Docker volumes by Compose or Swarm stack and review backup coverage.')">
        <template #actions>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                <div v-if="stacks.length" class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                    <input v-model="search" class="input sm:w-72" data-list-search :aria-label="t('Search')" :placeholder="t('Search stacks and volumes')">
                    <div class="flex items-center gap-3">
                        <p class="whitespace-nowrap text-sm text-slate-400">{{ t('{count} results', { count: filteredStacks.length }) }}</p>
                        <button type="button" class="btn-secondary gap-2" :aria-expanded="filtersVisible" :aria-label="filtersVisible ? t('Hide filters') : t('Show filters')" @click="filtersVisible = !filtersVisible">
                            <span>{{ t('Filters') }}</span>
                            <span v-if="activeAdvancedFilterCount" class="rounded-full bg-sky-400/20 px-2 py-0.5 text-xs text-sky-100">{{ activeAdvancedFilterCount }}</span>
                            <span class="h-2 w-2 border-b-2 border-r-2 border-current transition" :class="filtersVisible ? 'rotate-[225deg]' : 'rotate-45'" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <Link href="/volumes" class="btn-primary shrink-0">{{ t('View volumes') }}</Link>
            </div>
        </template>

        <div v-if="stacks.length && filtersVisible" class="card mb-4 p-4">
            <label class="block max-w-sm space-y-1">
                <span class="label">{{ t('Backup status') }}</span>
                <select v-model="backupFilter" class="input">
                    <option value="">{{ t('Any backup status') }}</option>
                    <option value="backed_up">{{ t('Backed up') }}</option>
                    <option value="configured">{{ t('Pending backup') }}</option>
                    <option value="unprotected">{{ t('Unprotected') }}</option>
                </select>
            </label>
            <button type="button" class="btn-secondary mt-3" :disabled="!hasActiveFilters" @click="resetFilters">{{ t('Reset filters') }}</button>
        </div>

        <div v-if="filteredStacks.length" class="space-y-6">
            <section v-for="stack in filteredStacks" :key="stack.name || 'no-stack'" class="card overflow-hidden">
                <div class="border-b border-white/10 p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="break-words text-xl font-semibold text-white">{{ stack.name || t('No stack') }}</h2>
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold" :class="stackConfigurationClass(stack.configuration_state)">{{ stackConfigurationLabel(stack.configuration_state) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-400">{{ t('{count} volumes', { count: stack.total_volumes }) }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3 lg:min-w-[30rem]">
                            <div class="rounded-xl bg-white/5 p-3"><p class="text-xs uppercase text-slate-500">{{ t('Backed up') }}</p><p class="mt-1 text-lg font-semibold text-emerald-100">{{ stack.backed_up_volumes }}</p></div>
                            <div class="rounded-xl bg-white/5 p-3"><p class="text-xs uppercase text-slate-500">{{ t('Pending backup') }}</p><p class="mt-1 text-lg font-semibold text-amber-100">{{ stack.configured_volumes }}</p></div>
                            <div class="rounded-xl bg-white/5 p-3"><p class="text-xs uppercase text-slate-500">{{ t('Unprotected') }}</p><p class="mt-1 text-lg font-semibold text-rose-100">{{ stack.unprotected_volumes }}</p></div>
                        </div>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-xs uppercase text-slate-500">{{ t('Configured jobs') }}</dt><dd class="mt-1 text-slate-200">{{ stack.configured_job_volumes }} / {{ stack.existing_volumes }}</dd></div>
                        <div><dt class="text-xs uppercase text-slate-500">{{ t('Missing volumes') }}</dt><dd class="mt-1 text-slate-200">{{ stack.missing_volumes }}</dd></div>
                        <div><dt class="text-xs uppercase text-slate-500">{{ t('Last backup') }}</dt><dd class="mt-1 text-slate-200">{{ formatDate(stack.last_backup_at) }}</dd></div>
                        <div><dt class="text-xs uppercase text-slate-500">{{ t('Backup size') }}</dt><dd class="mt-1 text-slate-200">{{ formatBytes(stack.last_backup_size_bytes, t('Unknown')) }}</dd></div>
                    </dl>
                </div>

                <div class="space-y-3 p-3 md:hidden">
                    <article v-for="volume in stack.volumes" :key="volume.id" class="space-y-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="break-all font-semibold text-white">{{ volume.name }}</h3>
                                <p class="mt-1 text-sm text-slate-400">{{ volume.driver || t('Unknown') }}</p>
                            </div>
                            <StatusBadge :status="volume.exists ? 'active' : 'error'" />
                        </div>
                        <dl class="grid gap-3 text-sm">
                            <div><dt class="text-xs uppercase text-slate-500">{{ t('Backup status') }}</dt><dd class="mt-1"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold" :class="backupStateClass(volume.backup_state)">{{ backupStateLabel(volume.backup_state) }}</span></dd></div>
                            <div v-if="volume.last_backup_at"><dt class="text-xs uppercase text-slate-500">{{ t('Last backup') }}</dt><dd class="mt-1 text-slate-200">{{ formatDate(volume.last_backup_at) }} / {{ formatBytes(volume.last_backup_size_bytes, t('Unknown')) }}</dd></div>
                        </dl>
                        <div class="flex flex-wrap gap-2">
                            <ActionIcon v-if="can.runDockerActions" :label="t('Create backup job')" icon="archive" :href="`/backup-jobs/create?volume=${encodeURIComponent(volume.name)}`" />
                            <ActionIcon :label="t('View jobs ({count})', { count: volume.related_jobs_count })" icon="eye" :href="jobsHref(volume.name)" />
                        </div>
                    </article>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5 text-left text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-3">{{ t('Name') }}</th>
                                <th class="px-4 py-3">{{ t('Driver') }}</th>
                                <th class="px-4 py-3">{{ t('Status') }}</th>
                                <th class="px-4 py-3">{{ t('Backup') }}</th>
                                <th class="px-4 py-3">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <tr v-for="volume in stack.volumes" :key="volume.id" class="hover:bg-slate-100 dark:hover:bg-white/[0.03]">
                                <td class="px-4 py-3 font-medium text-white">{{ volume.name }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ volume.driver || t('Unknown') }}</td>
                                <td class="px-4 py-3"><StatusBadge :status="volume.exists ? 'active' : 'error'" /></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold" :class="backupStateClass(volume.backup_state)">{{ backupStateLabel(volume.backup_state) }}</span>
                                    <p v-if="volume.last_backup_at" class="mt-1 whitespace-nowrap text-xs text-slate-400">{{ formatDate(volume.last_backup_at) }} / {{ formatBytes(volume.last_backup_size_bytes, t('Unknown')) }}</p>
                                    <p v-else class="mt-1 whitespace-nowrap text-xs text-slate-400">{{ t('Backup jobs: {count}', { count: volume.related_jobs_count }) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <ActionIcon v-if="can.runDockerActions" :label="t('Create backup job')" icon="archive" :href="`/backup-jobs/create?volume=${encodeURIComponent(volume.name)}`" />
                                        <ActionIcon :label="t('View jobs ({count})', { count: volume.related_jobs_count })" icon="eye" :href="jobsHref(volume.name)" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div v-else class="card p-10 text-center">
            <p class="text-lg font-semibold">{{ t('No stacks found.') }}</p>
            <button v-if="hasActiveFilters" type="button" class="btn-secondary mt-5" @click="resetFilters">{{ t('Reset filters') }}</button>
        </div>
    </AppLayout>
</template>
