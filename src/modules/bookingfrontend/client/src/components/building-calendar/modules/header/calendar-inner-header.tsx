import React, {Dispatch, FC} from 'react';
import {Button} from "@digdir/designsystemet-react";
import {ChevronLeftFirstIcon, ChevronRightLastIcon} from "@navikt/aksel-icons";
import styles from './calendar-inner-header.module.scss';
import {IBuilding} from "@/service/types/Building";

interface CalendarInnerHeaderProps {
    resourcesHidden: boolean
    setResourcesHidden: Dispatch<boolean>
    setView: Dispatch<string>;
    setLastCalendarView: Dispatch<void>;
    view: string;
    building: IBuilding;
}

const CalendarInnerHeader: FC<CalendarInnerHeaderProps> = (props) => {
    const {resourcesHidden, setResourcesHidden, view} = props
    return (
        <div style={{gridColumn: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
            <Button size={'sm'} icon={true} variant='tertiary'
                    style={{}}
                    className={styles.expandCollapseButton}
                    onClick={() => setResourcesHidden(!resourcesHidden)}
                    aria-label='Tertiary med ikon'>

                <ChevronLeftFirstIcon
                    className={`${styles.expandCollapseIcon} ${resourcesHidden ? styles.closed : styles.open}`}
                    fontSize='2.25rem'/>
                {props.building.name}

            </Button>
            <div style={{display: 'flex', gap: '1rem'}}>
                <Button variant={view !== 'listWeek' ? 'primary' : 'secondary'} onClick={() => {
                    props.setLastCalendarView()
                }}>Kalendervisning</Button>
                <Button variant={view === 'listWeek' ? 'primary' : 'secondary'} onClick={() => {
                    props.setView('listWeek')
                }}>Listevisning</Button>
            </div>
        </div>
    );
}

export default CalendarInnerHeader


