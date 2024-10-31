'use client';
import {
    useReactTable,
    getCoreRowModel,
    getSortedRowModel,
    flexRender,
    ColumnDef as TanStackColumnDef,
    Row,
    SortingState
} from '@tanstack/react-table';
import { useState, useMemo } from 'react';
import styles from './table.module.scss';
import type {ColumnDef, TableProps} from './table.types';
import {SortButton} from "@/components/gs-table/subcomponents/sort-button";
import {Chevron} from "@/components/gs-table/vectors/chevron";
import TableRow from "@/components/gs-table/row/table-row";

function Table<T>({
                      data,
                      columns,
                      empty,
                      enableSorting = true,
                      renderExpandedContent,
                      icon,
                      iconPadding,
                      rowStyle,
                      defaultSort = []
                  }: TableProps<T>) {
    const [sorting, setSorting] = useState<SortingState>(defaultSort);

    // Add default headers to columns if not provided
    const processedColumns = useMemo(() => {
        return columns.map(col => ({
            ...col,
            header: col.header || col.id || String(col.accessorKey) || '',
        }));
    }, [columns]);

    const gridTemplateColumns = useMemo(() => {
        return processedColumns
            .map((col) => {
                const size = col.meta?.size || 1;
                return `${size}fr`;
            })
            .join(' ');
    }, [processedColumns]);

    const table = useReactTable({
        data,
        columns: processedColumns,
        state: {
            sorting,
        },
        enableSorting,
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    return (
        <div className={styles.table}>
            <div className={styles.tableHeader}>
                {table.getHeaderGroups().map(headerGroup => (
                    <h4
                        key={headerGroup.id}
                        className={`${styles.tableRow} ${styles.tableHeaderBig}`}
                        style={{
                            gridTemplateColumns: gridTemplateColumns + (renderExpandedContent ? ' 4rem' : ''),
                            ...(icon ? {paddingLeft: iconPadding || '5.875rem'} : {})
                        }}
                    >
                        {headerGroup.headers.map(header => {
                            const meta = (header.column.columnDef as ColumnDef<T>).meta;
                            const canSort = header.column.getCanSort();

                            return (
                                <div
                                    key={header.id}
                                    className={`${styles.tableHeaderCol} ${
                                        canSort ? styles.clickable : styles.notClickable
                                    }`}
                                    onClick={header.column.getToggleSortingHandler()}
                                >
                                    {!meta?.hideHeader && (
                                        <span className={styles.capitalize}>
                                            {flexRender(
                                                header.column.columnDef.header,
                                                header.getContext()
                                            )}
                                        </span>
                                    )}
                                    {canSort && (
                                        <SortButton
                                            direction={
                                                header.column.getIsSorted() === 'desc'
                                                    ? 'desc'
                                                    : header.column.getIsSorted() === 'asc'
                                                        ? 'asc'
                                                        : undefined
                                            }
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </h4>
                ))}
            </div>


            {data.length === 0 && empty ? (
                empty
            ) : (
                table.getRowModel().rows.map(row => (
                    <TableRow
                        key={row.id}
                        row={row}
                        gridTemplateColumns={gridTemplateColumns}
                        icon={icon}
                        renderExpandedContent={renderExpandedContent}
                        rowStyle={rowStyle}
                    />
                ))
            )}
        </div>
    );
}

export default Table;