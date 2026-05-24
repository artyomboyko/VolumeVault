<script setup lang="ts">
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { formatBytes } from '@/Composables/useFormatBytes';

defineProps<{
    stats: Record<string, any>;
    recentBackupRuns: any[];
    recentRestoreRuns: any[];
    jobsWithErrors: any[];
}>();

const { t, formatDate } = useI18n();

const statLabels: Record<string, string> = {
    total_volumes: 'Total volumes',
    existing_volumes: 'Existing volumes',
    missing_volumes: 'Missing volumes',
    backed_up_volumes: 'Backed up volumes',
    configured_volumes: 'Pending backup volumes',
    unprotected_volumes: 'Unprotected volumes',
    total_jobs: 'Total jobs',
    active_jobs: 'Active jobs',
    paused_jobs: 'Paused jobs',
    error_jobs: 'Error jobs',
    last_backup_run_status: 'Last backup run status',
    last_successful_backup_size: 'Last successful backup size',
    next_scheduled_backup: 'Next scheduled backup',
};

const statLabel = (key: string) => t(statLabels[key] || key.replaceAll('_', ' '));
const statValue = (key: string, value: any) => {
    if (key.includes('scheduled')) return formatDate(value);
    if (key.includes('size')) return formatBytes(value, t('Unknown'));
    if (key.includes('status')) return value ? t(String(value)) : t('None');

    return value ?? t('None');
};
</script>

<template>
    <Head :title="t('Dashboard')" />
    <AppLayout :title="t('Dashboard')">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="(value, key) in stats" :key="key" class="card p-5" :class="{ hidden: key === 'last_successful_backup_size' }">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ statLabel(String(key)) }}</p>
                <p class="mt-3 break-words text-2xl font-bold text-white">{{ statValue(String(key), value) }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="card p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ t('Recent backup runs') }}</h2>
                    <Link href="/backup-jobs" class="text-sm text-sky-300 hover:text-sky-200">{{ t('View jobs') }}</Link>
                </div>
                <div v-if="recentBackupRuns.length" class="space-y-3">
                    <Link v-for="run in recentBackupRuns" :key="run.id" :href="`/backup-runs/${run.id}`" class="flex flex-col gap-2 rounded-xl bg-white/5 px-4 py-3 hover:bg-white/10 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-words font-medium">{{ run.job?.name || t('Backup run #{id}', { id: run.id }) }}</p>
                            <p class="text-xs text-slate-400">{{ formatDate(run.started_at || run.created_at) }} <span v-if="run.backup_size_bytes">/ {{ formatBytes(run.backup_size_bytes) }}</span></p>
                        </div>
                        <StatusBadge :status="run.status" />
                    </Link>
                </div>
                <p v-else class="rounded-xl border border-dashed border-white/10 p-4 text-sm text-slate-400">{{ t('No backup runs yet.') }}</p>
            </section>

            <section class="card p-5">
                <h2 class="mb-4 text-lg font-semibold">{{ t('Recent restore runs') }}</h2>
                <div v-if="recentRestoreRuns.length" class="space-y-3">
                    <Link v-for="run in recentRestoreRuns" :key="run.id" :href="`/restore-runs/${run.id}`" class="flex flex-col gap-2 rounded-xl bg-white/5 px-4 py-3 hover:bg-white/10 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-all font-medium">{{ run.source_volume_name }} to {{ run.target_volume_name }}</p>
                            <p class="text-xs text-slate-400">{{ formatDate(run.started_at || run.created_at) }}</p>
                        </div>
                        <StatusBadge :status="run.status" />
                    </Link>
                </div>
                <p v-else class="rounded-xl border border-dashed border-white/10 p-4 text-sm text-slate-400">{{ t('No restore runs yet.') }}</p>
            </section>
        </div>

        <section class="card mt-6 p-5">
            <h2 class="mb-4 text-lg font-semibold">{{ t('Jobs with errors') }}</h2>
            <div v-if="jobsWithErrors.length" class="space-y-3">
                <Link v-for="job in jobsWithErrors" :key="job.id" :href="`/backup-jobs/${job.id}`" class="block rounded-xl bg-rose-400/10 px-4 py-3 hover:bg-rose-400/15">
                    <p class="break-words font-medium text-rose-100">{{ job.name }}</p>
                    <p class="break-words text-sm text-rose-200/80">{{ job.last_error || t('Unknown error') }}</p>
                </Link>
            </div>
            <p v-else class="text-sm text-slate-400">{{ t('No jobs are currently in error.') }}</p>
        </section>
    </AppLayout>
</template>
