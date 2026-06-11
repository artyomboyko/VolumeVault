<script setup lang="ts">
defineProps<{
    open: boolean;
}>();
</script>

<template>
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <template v-if="open">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
            <circle cx="12" cy="12" r="3" />
        </template>
        <template v-else>
            <path d="M3 9c2.8 4 6 5.5 9 5.5s6.2-1.5 9-5.5" />
            <path d="M5.5 13l-1.3 2" />
            <path d="M12 14.7v2.3" />
            <path d="M18.5 13l1.3 2" />
        </template>
    </svg>
</template>
