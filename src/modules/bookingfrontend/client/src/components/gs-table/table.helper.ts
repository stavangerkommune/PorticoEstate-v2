import { TableColumnDefinition } from './table.types';

export function isTableColumnDefinition<T>(
    def: keyof T | TableColumnDefinition<T>
): def is TableColumnDefinition<T> {
    return typeof def !== 'string';
}

const DefaultCompare = (dataA: unknown, dataB: unknown) => {
    if (!dataA && !dataB) {
        return 0;
    }
    if (!dataA) {
        return -1;
    }
    // if (!dataB) {
    return 1;
    // }
};

export const StringCompare = (strA: string | undefined, strB: string | undefined) => {
    if (!strA || !strB) {
        return DefaultCompare(strA, strB);
    }
    return strA.localeCompare(strB);
};
export const NumberCompare = (numA: number | undefined, numB: number | undefined) => {
    if (!numA || !numB) {
        return DefaultCompare(numA, numB);
    }
    return numA - numB;
};

export const DateCompare = (dateA: Date, dateB: Date) =>
    NumberCompare(dateA?.getTime(), dateB?.getTime());
