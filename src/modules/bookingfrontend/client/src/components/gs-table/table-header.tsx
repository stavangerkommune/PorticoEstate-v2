import { Fragment, ReactElement, useEffect, useState } from 'react';
import { isTableColumnDefinition } from './table.helper';
import { SortButton } from './subcomponents/sort-button';
import { TableOptions } from './table.types';
import { Capitalize } from './lib/helpers';
import styles from './table.module.scss';

interface TableHeaderProps<T> {
    options: TableOptions<T>;
    gridTemplateColumns?: string;
    onSort: (key: keyof T | null) => void;
    sort?: { key: keyof T; direction: 'desc' | 'asc' };
}

function TableHeader<T>(props: TableHeaderProps<T>): ReactElement {
    const { options, gridTemplateColumns, sort, onSort } = props;

    const [selectedKey, setSelectedKey] = useState<keyof T>();

    useEffect(() => {
        const default_sort = options.columns.find(
            (c) => isTableColumnDefinition(c) && c.sortCompare
        );
        if (default_sort && isTableColumnDefinition(default_sort)) {
            setSelectedKey(default_sort?.key);
        }
    }, [options.columns]);

    return (
        <div className={`${styles.tableHeaderContainer}`}>
            <div className={`${styles.tableHeader} ${styles.tableHeaderSmall}`}>
                <div className={`${styles.tableHeaderCol}`}>
                    {selectedKey !== undefined && (
                        <Fragment>
                            <select
                                onChange={(e) => {
                                    const newKey = e.target.value as keyof T;
                                    if (sort?.key && newKey !== sort.key) {
                                        onSort(null);
                                    }
                                    setSelectedKey(newKey);
                                }}
                                value={selectedKey as string}
                            >
                                {options.columns.map((column) => {
                                    if (!isTableColumnDefinition(column)) {
                                        return null;
                                    }
                                    if (!column.sortCompare) {
                                        return null;
                                    }
                                    return (
                                        <option
                                            key={'thead-' + String(column.key)}
                                            value={String(column.key)}
                                            className={styles.capitalize}
                                        >
                                            {Capitalize(!column.title ? column.key : column.title)}
                                        </option>
                                    );
                                })}
                            </select>
                            <button
                                className={`${styles.clickable} unstyled`}
                                onClick={() => onSort(selectedKey)}
                                style={{ marginLeft: '1rem' }}
                            >
                                <SortButton
                                    direction={
                                        (selectedKey === sort?.key && sort?.direction) || undefined
                                    }
                                />
                            </button>
                        </Fragment>
                    )}
                </div>
            </div>
            <h4
                className={`${styles.tableHeader} ${styles.tableHeaderBig}`}
                style={{
                    gridTemplateColumns:
                        gridTemplateColumns + (options.expandedContent ? ' 4rem' : ''),
                    ...(options.icon ? { paddingLeft: options.iconPadding || '5.875rem' } : {})
                }}
            >
                {options.columns.map((column) => {
                    if (!isTableColumnDefinition(column)) {
                        return (
                            <div key={'thead-' + String(column)} className={`${styles.tableHeaderCol}`}>
                                <span className={styles.capitalize}>{column.toString()}</span>
                            </div>
                        );
                    }

                    if (column.sortCompare) {
                        return (
                            <button
                                key={'thead-' + String(column.key)}
                                className={`${styles.tableHeaderCol} ${
                                    styles.clickable
                                } unstyled`}
                                onClick={() => onSort(column.key)}
                            >
                            <span className={styles.capitalize}>
                                {column.hideTitle ? '': (!column.title ? column.key.toString() : column.title)}
                            </span>
                                <SortButton
                                    direction={
                                        (column.key === sort?.key && sort.direction) || undefined
                                    }
                                />
                            </button>
                        );
                    }
                    return (
                        <span
                            key={'thead-' + String(column.key)}
                            className={`${styles.tableHeaderCol} ${
                                styles.notClickable
                            } unstyled`}
                        >
                            <span className={styles.capitalize}>
                                {column.hideTitle ? '': (!column.title ? column.key.toString() : column.title)}
                            </span>
                        </span>
                    );
                })}
            </h4>
        </div>
    );
}

export default TableHeader;
