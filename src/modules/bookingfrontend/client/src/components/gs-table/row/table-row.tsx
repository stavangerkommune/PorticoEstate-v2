import { ReactElement, useMemo } from 'react';
import { TableColumnDefinition, TableOptions } from '../table.types';
import { isTableColumnDefinition } from '../table.helper';
import RowExpand from './row-expand';
import styles from '../table.module.scss';

interface TableRowProps<T> {
    options: TableOptions<T>;
    gridTemplateColumns?: string;
    data: T;
    _key: string;
}

function TableRow<T>(props: TableRowProps<T>): ReactElement {
    const { options, data, gridTemplateColumns, _key } = props;

    const columns: { content: any; columnDef: TableColumnDefinition<T> }[] = useMemo(() => {
        return options.columns.map((columnDef) => {
            if (!isTableColumnDefinition(columnDef)) {
                return {
                    content: data[columnDef],
                    columnDef: { key: columnDef },
                };
            }
            if (columnDef.render) {
                return {
                    content: columnDef.render(data[columnDef.key], data),
                    columnDef: columnDef,
                };
            }
            return { content: data[columnDef.key], columnDef: columnDef };
        });
    }, [options, data]);

    const containerColumns = useMemo(() => {
        let content = '1fr';
        if (options.icon) {
            content = 'auto ' + content;
        }
        if (options.expandedContent) {
            content = content + ' 4rem';
        }
        return content;
    }, [options]);

    return (
        <div className={styles.tableRowContainer} key={_key} style={{ gridTemplateColumns: containerColumns,gridTemplateAreas: options.expandedContent ? `"line expand" "content content"` : `"line" "content"`, gap: "0 0.5rem", ...(options.rowStyle ? options.rowStyle(data) : {}) }}>
            {options.icon && <div style={{ margin: '0.5rem', marginRight:'16px', marginLeft: '0', display: 'flex', justifyContent: 'center', alignItems: 'center' }}>{options.icon(data)}</div>}
            <div className={styles.tableRow} style={{ gridTemplateColumns: gridTemplateColumns, gridArea: 'line' }}>
                {columns.map((column, colIndex) => (
                    <div
                        className={`${styles.centerCol} ${
                            column.columnDef.size && column.columnDef.size > 1 ? styles.bigCol : ''
                        }`}
                        style={{
                            justifyContent: column.columnDef?.contentAlign
                                ? column.columnDef.contentAlign
                                : 'flex-start',
                        }}
                        key={_key + '-col-' + column.columnDef.key.toString()}
                    >
                        {/* Only shown in smallmode */}
                        {!column.columnDef.smallHideTitle && (
                            <div className={`heading-s ${styles.columnMame}`}>
                                <span className={styles.capitalize}>
                                    {!column.columnDef.title
                                        ? column.columnDef.key.toString()
                                        : column.columnDef.title}
                                </span>
                            </div>
                        )}
                        {/*-------------------------*/}
                        {column.content}
                    </div>
                ))}
            </div>
            {options.expandedContent && <RowExpand>{options.expandedContent(data)}</RowExpand>}
        </div>
    );
}

export default TableRow;
