<script setup lang="ts">
defineProps<{
    label: string;
    value: string;
}>();
</script>

<template>
    <div class="card h-full p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ label }}</p>
        <p class="mt-3 break-words text-2xl font-bold text-white">{{ value }}</p>
    </div>
</template>
