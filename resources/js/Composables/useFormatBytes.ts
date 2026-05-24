export function formatBytes(bytes?: number | null, fallback = '-'): string {
    if (bytes === null || bytes === undefined) return fallback;
    if (bytes === 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${(bytes / Math.pow(1024, index)).toFixed(1)} ${units[index]}`;
}
