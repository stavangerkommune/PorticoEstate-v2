'use client';
import { ReactElement, useMemo, useState } from 'react';
import TableRow from './row/table-row';
import { TableOptions } from './table.types';
import { isTableColumnDefinition } from './table.helper';
import TableHeader from './table-header';
import styles from './table.module.scss';

interface TableProps<T> {
    options: TableOptions<T>;
    data: T[];
    empty?: JSX.Element;
}

function Table<T>(props: TableProps<T>): ReactElement {
    const { options, data } = props;
    const [sort, setSort] = useState<{ key: keyof T; direction: 'desc' | 'asc' }>();

    const rowData = useMemo(() => {
        if (!sort) {
            return data;
        }
        const columnDef = options.columns.find(
            (col) => isTableColumnDefinition(col) && col.key === sort.key
        );
        if (!columnDef || !isTableColumnDefinition(columnDef) || !columnDef.sortCompare) {
            return data;
        }

        return [...data].sort((a, b) => {
            const res = columnDef.sortCompare!(a[columnDef.key], b[columnDef.key], a, b);
            return sort.direction === 'desc' ? -res : res;
        });
    }, [sort, data, options.columns]);

    const gridTemplateColumns = useMemo(() => {
        return options.columns
            .map((col) => {
                if (isTableColumnDefinition(col)) {
                    if (col.size) {
                        return `${col.size}fr`;
                    }
                    return '1fr';
                }
                return '1fr';
            })
            .join(' ');
    }, [options.columns]);

    const onSort = (key: keyof T | null) => {
        if (key === null) {
            setSort(undefined);
            return;
        }
        if (sort?.key === key) {
            if (sort.direction === 'asc') {
                setSort(undefined);
                return;
            }
            setSort({ key: key, direction: 'asc' });
            return;
        }
        setSort({ key: key, direction: 'desc' });
    };

    return (
        <div className={styles.table}>
            <TableHeader
                onSort={onSort}
                sort={sort}
                options={options}
                gridTemplateColumns={gridTemplateColumns}
            />
            {(rowData.length !== 0 || !props.empty) && rowData.map((row) => {
                return (
                    <TableRow
                        _key={`tableRow-${row[options.keyField]}`}
                        key={`tableRow-${row[options.keyField]}`}
                        options={options}
                        gridTemplateColumns={gridTemplateColumns}
                        data={row}
                    />
                );
            }) || props.empty}
        </div>
    );
}

export default Table;
