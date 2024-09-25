import React, {Dispatch, FC, MutableRefObject} from 'react';
import {Button} from "@digdir/designsystemet-react";
import {ChevronLeftFirstIcon, ChevronLeftIcon, ChevronRightIcon, ChevronRightLastIcon} from "@navikt/aksel-icons";
import styles from './calendar-inner-header.module.scss';
import {IBuilding} from "@/service/types/Building";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";
import CalendarDatePicker from "@/components/building-calendar/modules/header/calendar-date-picker";
import FullCalendar from "@fullcalendar/react";
import ButtonGroup from "@/components/button-group/button-group";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faCalendar} from "@fortawesome/free-regular-svg-icons";
import {faTableList} from "@fortawesome/free-solid-svg-icons";

interface CalendarInnerHeaderProps {
    resourcesHidden: boolean
    setResourcesHidden: Dispatch<boolean>
    setView: Dispatch<string>;
    setLastCalendarView: Dispatch<void>;
    view: string;
    building: IBuilding;
    calendarRef: MutableRefObject<FullCalendar | null>;

}

const CalendarInnerHeader: FC<CalendarInnerHeaderProps> = (props) => {
    const t = useTrans();
    const {resourcesHidden, setResourcesHidden, view, calendarRef, setView} = props
    const c = calendarRef.current;
    if (!c) {
        return null;
    }
    const calendarApi = c.getApi();
    const currentDate = calendarApi ? calendarApi.getDate() : new Date();

    return (
        <div className={styles.innerHeader}>
            <div className={styles.flexBox}>
                <Button size={'sm'} icon={true} variant='tertiary'
                        style={{}}
                        className={`${styles.expandCollapseButton} ${resourcesHidden ? styles.closed : styles.open}`}
                        onClick={() => setResourcesHidden(!resourcesHidden)}>

                    <ChevronLeftFirstIcon
                        className={`${styles.expandCollapseIcon} ${resourcesHidden ? styles.closed : styles.open}`}
                        fontSize='2.25rem'/>
                    {props.building.name}

                </Button>
                <div className={styles.datePicker}>
                    <Button size={'sm'} icon={true} variant='tertiary' style={{borderRadius: "50%"}}
                            onClick={() => {
                                if (c) {
                                    calendarApi.prev();
                                }
                            }}
                            aria-label='Tertiary med ikon'>
                        <ChevronLeftIcon fontSize='2.25rem'/>
                    </Button>
                    <CalendarDatePicker currentDate={currentDate} view={c.getApi().view.type}
                                        onDateChange={(v) => v && calendarApi.gotoDate(v)}/>
                    <Button icon={true} size={'sm'} variant='tertiary' style={{borderRadius: "50%"}}
                            onClick={() => {
                                if (c) {
                                    calendarApi.next();
                                }
                            }}
                            aria-label='Tertiary med ikon'>
                        <ChevronRightIcon fontSize='2.25rem'/>
                    </Button>
                </div>
            </div>
            <div className={styles.flexBox}>

                <ButtonGroup className={styles.modeSelect}>
                    <Button variant={view === 'timeGridDay' ? 'primary' : 'secondary'} size={'sm'}
                            className={'captialize'}

                            onClick={() => setView('timeGridDay')}>{t('bookingfrontend.day')}</Button>
                    <Button variant={view === 'timeGridWeek' ? 'primary' : 'secondary'} size={'sm'}
                            className={'captialize'}

                            onClick={() => setView('timeGridWeek')}>{t('bookingfrontend.week')}</Button>
                    <Button variant={view === 'dayGridMonth' ? 'primary' : 'secondary'} size={'sm'}
                            className={'captialize'}

                            onClick={() => setView('dayGridMonth')}>{t('bookingfrontend.month')}</Button>

                </ButtonGroup>

                <ButtonGroup className={styles.modeSelect}>
                    <Button variant={view !== 'listWeek' ? 'primary' : 'secondary'} size={'sm'}
                            className={'captialize'} onClick={() => {
                        props.setLastCalendarView()
                    }}><FontAwesomeIcon icon={faCalendar}/> {t('bookingfrontend.calendar_view')}</Button>
                    <Button variant={view === 'listWeek' ? 'primary' : 'secondary'} size={'sm'}
                            className={'captialize'} onClick={() => {
                        props.setView('listWeek')
                    }}><FontAwesomeIcon icon={faTableList}/> {t('bookingfrontend.list_view')}</Button>
                </ButtonGroup>
            </div>
        </div>
    );
}

export default CalendarInnerHeader


