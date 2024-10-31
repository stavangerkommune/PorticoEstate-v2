import { ReactElement, CSSProperties } from 'react';
import { Row, flexRender } from '@tanstack/react-table';
import { ColumnDef } from '../table.types';
import RowExpand from './row-expand';
import styles from '../table.module.scss';

interface TableRowProps<T> {
    row: Row<T>;
    gridTemplateColumns?: string;
    icon?: (data: T) => ReactElement;
    renderExpandedContent?: (data: T) => ReactElement;
    rowStyle?: (data: T) => CSSProperties | undefined;
}

function TableRow<T>(props: TableRowProps<T>): ReactElement {
    const { row, gridTemplateColumns, icon, renderExpandedContent, rowStyle } = props;

    return (
        <div
            className={styles.tableRowContainer}
            key={row.id}
            style={{
                gridTemplateColumns: renderExpandedContent ? `1fr auto` : '1fr',
                gridTemplateAreas: renderExpandedContent
                    ? `"line expand" "content content"`
                    : `"line" "content"`,
                gap: "0 0.5rem",
                ...(rowStyle?.(row.original) || {})
            }}
        >
            {icon && (
                <div style={{
                    margin: '0.5rem',
                    marginRight: '16px',
                    marginLeft: '0',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center'
                }}>
                    {icon(row.original)}
                </div>
            )}

            <div
                className={styles.tableRow}
                style={{
                    gridTemplateColumns,
                    gridArea: 'line'
                }}
            >
                {row.getVisibleCells().map(cell => {
                    const meta = (cell.column.columnDef as ColumnDef<T>).meta;
                    const header = cell.column.columnDef.header;
                    const headerContent = typeof header === 'string'
                        ? header
                        : cell.column.id;

                    return (
                        <div
                            key={cell.id}
                            className={`${styles.centerCol} ${
                                meta?.size &&  meta.size > 1 ? styles.bigCol : ''
                            }`}
                            style={{
                                justifyContent: meta?.align === 'end'
                                    ? 'flex-end'
                                    : meta?.align === 'center'
                                        ? 'center'
                                        : 'flex-start',
                            }}
                        >
                            {!meta?.smallHideTitle && (
                                <div className={`heading-s ${styles.columnName}`}>
                                    <span className={styles.capitalize}>
                                        {headerContent}
                                    </span>
                                </div>
                            )}
                            {flexRender(
                                cell.column.columnDef.cell,
                                cell.getContext()
                            )}
                        </div>
                    );
                })}
            </div>

            {renderExpandedContent && (
                <RowExpand>
                    {renderExpandedContent(row.original)}
                </RowExpand>
            )}
        </div>
    );
}

export default TableRow;