export const sizeUnits = [
    { label: 'B', multiplier: 1 },
    { label: 'KB', multiplier: 1024 },
    { label: 'MB', multiplier: 1024 ** 2 },
    { label: 'GB', multiplier: 1024 ** 3 },
    { label: 'TB', multiplier: 1024 ** 4 },
] as const;

export type SizeUnit = typeof sizeUnits[number]['label'];

export function formatBytes(bytes?: number | null, fallback = '-'): string {
    if (bytes === null || bytes === undefined) return fallback;
    if (bytes === 0) return '0 B';

    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), sizeUnits.length - 1);

    return `${(bytes / sizeUnits[index].multiplier).toFixed(1)} ${sizeUnits[index].label}`;
}

export function bestSizeUnit(bytes?: number | null): SizeUnit {
    if (!bytes || bytes < 1024) return 'B';

    const unit = [...sizeUnits].reverse().find((unit) => bytes >= unit.multiplier);

    return unit?.label || 'B';
}

export function bytesToUnitValue(bytes: number | null | undefined, unit: SizeUnit): number | '' {
    if (bytes === null || bytes === undefined) return '';

    const multiplier = sizeUnits.find((sizeUnit) => sizeUnit.label === unit)?.multiplier || 1;
    const value = Number(bytes) / multiplier;

    return Number.isInteger(value) ? value : Number(value.toFixed(2));
}

export function unitValueToBytes(value: string | number, unit: SizeUnit): number | null {
    if (value === '') return null;

    const parsedValue = Number(value);
    const multiplier = sizeUnits.find((sizeUnit) => sizeUnit.label === unit)?.multiplier || 1;

    return Number.isFinite(parsedValue) ? Math.max(0, Math.round(parsedValue * multiplier)) : null;
}
