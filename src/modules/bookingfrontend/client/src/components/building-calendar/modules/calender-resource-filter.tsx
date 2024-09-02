import React, {FC} from 'react';
import styles from './calender-resource-filter.module.scss';
import ColourCircle from "@/components/building-calendar/modules/colour-circle/colour-circle";
import {Checkbox, Button} from "@digdir/designsystemet-react";
import {useTempEvents} from "@/components/building-calendar/calendar-context";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";

export interface CalendarResourceFilterOption {
    value: string;
    label: string;
    color?: string;
}

interface CalendarResourceFilterProps {
    hidden: boolean;
    resourceOptions: CalendarResourceFilterOption[];
    enabledResources: Set<string>;
    onToggle: (resourceId: string) => void;
    onToggleAll: () => void;
}

const CalendarResourceFilter: FC<CalendarResourceFilterProps> = ({
                                                                     resourceOptions,
                                                                     enabledResources,
                                                                     onToggle,
                                                                     onToggleAll,
                                                                     hidden
                                                                 }) => {
    const t = useTrans();
    const {tempEvents} = useTempEvents();

    return (
        <div className={`${styles.resourceToggleContainer} ${hidden ? styles.hidden : ''}`}
        >
            <div className={styles.toggleAllContainer}>
                <Button
                    onClick={onToggleAll}
                    className={styles.toggleAllButton}
                    variant={'tertiary'}
                    size={'sm'}
                >
                    {enabledResources.size === resourceOptions.length ? t('bookingfrontend.deselect_all') : t('common.select all')}
                </Button>
            </div>
            {resourceOptions.map(resource => (
                <div key={resource.value}
                     className={`${styles.resourceItem} ${enabledResources.has(resource.value) ? styles.active : ''}`}>
                    <Checkbox
                        value={`${resource.value}`}
                        id={`resource-${resource.value}`}
                        checked={enabledResources.has(resource.value)}
                        onChange={() => onToggle(resource.value)}
                        className={styles.resourceCheckbox}
                        disabled={Object.values(tempEvents).length > 1}
                    >
                        <label
                            htmlFor={`resource-${resource.value}`}
                            className={styles.resourceLabel}
                        >
                            {resource.label}
                            <ColourCircle resourceId={+resource.value} size={'medium'}/>
                        </label>
                    </Checkbox>

                </div>
            ))}
        </div>
    );
};

export default CalendarResourceFilter;
