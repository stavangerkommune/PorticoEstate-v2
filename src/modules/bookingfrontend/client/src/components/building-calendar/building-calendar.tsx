import React, {Dispatch, FC, useCallback, useEffect, useMemo, useRef, useState} from 'react';
import {DateTime, Settings} from 'luxon'
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin, {EventResizeDoneArg} from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import {IBuildingResource, IEvent, Season} from "@/service/pecalendar.types";
import {
    DateSelectArg,
    DatesSetArg,
    EventDropArg,
    EventInput,
} from "@fullcalendar/core";
import styles from './building-calender.module.scss'
import {useColours} from "@/service/hooks/Colours";
import {FCallEventConverter} from "@/components/building-calendar/util/event-converter";
import CalendarResourceFilter, {
    CalendarResourceFilterOption
} from "@/components/building-calendar/modules/calender-resource-filter";
import EventContent from "@/components/building-calendar/modules/event/content/event-content";
import CalendarHeader from "@/components/building-calendar/modules/header/calendar-header";
import EventPopper from "@/components/building-calendar/modules/event/popper/event-popper";
import CalendarInnerHeader from "@/components/building-calendar/modules/header/calendar-inner-header";
import {usePopperData} from "@/service/api/event-info";
import {
    FCallBackgroundEvent, FCallBaseEvent,
    FCallEvent,
    FCallTempEvent, FCEventClickArg,
    FCEventContentArg
} from "@/components/building-calendar/building-calendar.types";
import CalendarProvider from "@/components/building-calendar/calendar-context";
import EventContentTemp from "@/components/building-calendar/modules/event/content/event-content-temp";
import {IBuilding} from "@/service/types/Building";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";

interface BuildingCalendarProps {
    events: IEvent[];
    resources: Record<string, IBuildingResource>;
    onDateChange: Dispatch<DatesSetArg>
    seasons: Season[];
    building: IBuilding;
    initialDate: DateTime;
}

Settings.defaultLocale = "nb";


const BuildingCalendar: FC<BuildingCalendarProps> = (props) => {
    const t = useTrans();
    const {events} = props;
    const [currentDate, setCurrentDate] = useState<DateTime>(props.initialDate);
    const [calendarEvents, setCalendarEvents] = useState<FCallEvent[]>([]);
    const colours = useColours();
    const [slotMinTime, setSlotMinTime] = useState('00:00:00');
    const [slotMaxTime, setSlotMaxTime] = useState('24:00:00');
    const [selectedEvent, setSelectedEvent] = useState<FCallEvent | FCallTempEvent | null>(null);
    const [popperAnchorEl, setPopperAnchorEl] = useState<HTMLElement | null>(null);
    const calendarRef = useRef<FullCalendar | null>(null);
    const [view, setView] = useState<string>('timeGridWeek');
    const [resourcesHidden, setSResourcesHidden] = useState<boolean>(false);
    const [resourcesContainerRendered, setResourcesContainerRendered] = useState<boolean>(true)
    const [lastCalendarView, setLastCalendarView] = useState<string>('timeGridWeek');
    const [tempEvents, setTempEvents] = useState<Record<string, FCallTempEvent>>({});
    const eventInfos = usePopperData(
        events.filter(e => e.type === 'event').map(e => e.id),
        events.filter(e => e.type === 'allocation').map(e => e.id),
        events.filter(e => e.type === 'booking').map(e => e.id)
    );


    useEffect(() => {
        if (view === 'listWeek') {
            return;
        }
        if (view === lastCalendarView) {
            return;
        }
        setLastCalendarView(view)

    }, [view, lastCalendarView]);

    const resourceOptions = useMemo<CalendarResourceFilterOption[]>(() => {
        return Object.values(props.resources).map((resource, index) => ({
            value: resource.id.toString(),
            label: resource.name,
            color: colours?.[index % colours.length]
        }));
    }, [props.resources, colours]);

    const [enabledResources, setEnabledResources] = useState<Set<string>>(
        new Set(resourceOptions.map(option => option.value))
    );
    const setResourcesHidden = (v: boolean) => {
        if (!v) {
            setResourcesContainerRendered(true);
        }
        setSResourcesHidden(v)
    }
    const handleDateClick = (arg: { date: Date; dateStr: string; allDay: boolean; }) => {
        if (view === 'dayGridMonth') {
            const clickedDate = DateTime.fromJSDate(arg.date);
            setCurrentDate(clickedDate);
            setView('timeGridWeek');
            calendarRef.current?.getApi().gotoDate(clickedDate.toJSDate());
        }
    };

    const handleDateSelect = useCallback((selectInfo: DateSelectArg) => {
        if (selectInfo.view.type === 'dayGridMonth') {
            return;
        }
        // console.log(enabledResources, props.resources);
        const title = t('bookingfrontend.new application');

        const newEvent: FCallTempEvent = {
            id: `temp-${Date.now()}`,
            title,
            start: selectInfo.start,
            end: selectInfo.end,
            allDay: selectInfo.allDay,
            editable: true,
            extendedProps: {
                type: 'temporary',
                resources: [...enabledResources].map(a => props.resources[a]),
            },
        };
        setTempEvents(prev => ({...prev, [newEvent.id]: newEvent}));
        selectInfo.view.calendar.unselect(); // Clear selection
    }, [props.resources, enabledResources]);


    const handleEventClick = useCallback((clickInfo: FCEventClickArg<FCallBaseEvent>) => {
        // Check if the clicked event is a background event
        if ('display' in clickInfo.event && clickInfo.event.display === 'background') {
            // Do not open popper for background events
            return;
        }

        // Check if the event is a valid, interactive event
        if ('id' in clickInfo.event && clickInfo.event.id) {
            setSelectedEvent(clickInfo.event);
            setPopperAnchorEl(clickInfo.el);
        }
    }, []);


    const calculateAbsoluteMinMaxTimes = useCallback(() => {
        let minTime = '24:00:00';
        let maxTime = '00:00:00';
        // Check seasons
        props.seasons.forEach(season => {
            if (season.from_ < minTime) minTime = season.from_;
            if (season.to_ > maxTime) maxTime = season.to_;
        });

        // Check events
        events.forEach(event => {
            if (event.from < minTime) minTime = event.from;
            if (event.to > maxTime) maxTime = event.to;
        });


        setSlotMinTime(minTime === "24:00:00" ? '06:00:00' : minTime);
        setSlotMaxTime(maxTime === "00:00:00" ? '24:00:00' : maxTime);
    }, [props.seasons, events]);

    useEffect(() => {
        calculateAbsoluteMinMaxTimes();
    }, [calculateAbsoluteMinMaxTimes]);


    const renderBackgroundEvents = useCallback(() => {
        const backgroundEvents: FCallBackgroundEvent[] = [];
        const startDate = currentDate.startOf('week');
        const endDate = startDate.plus({weeks: 4});

        for (let date = startDate; date < endDate; date = date.plus({days: 1})) {
            const dayOfWeek = date.weekday;
            const season = props.seasons.find(s => s.wday === dayOfWeek);

            if (season) {
                // Add background event for time before opening
                backgroundEvents.push({
                    start: new Date(date.toFormat("yyyy-MM-dd'T00:00:00'")),
                    end: new Date(date.toFormat(`yyyy-MM-dd'T${season.from_}'`)),
                    display: 'background',
                    classNames: styles.closedHours,
                    extendedProps: {
                        type: 'background'
                    }
                });

                // Add background event for time after closing
                backgroundEvents.push({
                    start: new Date(date.toFormat(`yyyy-MM-dd'T${season.to_}'`)),
                    end: new Date(date.plus({days: 1}).toFormat("yyyy-MM-dd'T00:00:00'")),
                    display: 'background',
                    classNames: styles.closedHours,
                    extendedProps: {
                        type: 'background'
                    }
                });
            }
        }

        return backgroundEvents;
    }, [currentDate, props.seasons]);

    const resourceToIds = useMemo(() => {
        return Object.values(props.resources).map(res => res.id).reduce((coll, curr, indx) => {
            return {[curr]: indx, ...coll}
        }, {})
    }, [props.resources])


    const handleEventResize = useCallback((resizeInfo: EventResizeDoneArg | EventDropArg) => {
        if (resizeInfo.event.extendedProps?.type === 'temporary') {
            setTempEvents(prev => ({
                ...prev,
                [resizeInfo.event.id]: {
                    ...prev[resizeInfo.event.id],
                    start: resizeInfo.event.start as Date,
                    end: resizeInfo.event.end as Date
                }
            }))

        }

    }, []);

    const tempEventArr = useMemo(() => Object.values(tempEvents), [tempEvents])

    const popperPlacement = () => {
        switch (calendarRef.current?.getApi().view.type) {
            case 'timeGridDay':
                return 'auto';
            case 'listWeek':
                return 'bottom-start';
            default:
                return 'right-start';
        }

    }

    useEffect(() => {
        if (!colours || !resourceToIds) {
            return;
        }
        const filteredEvents = events
            .map((e) => FCallEventConverter(e, colours, resourceToIds, enabledResources)!)
            .filter(e => e);
        // console.log(events, filteredEvents)
        setCalendarEvents(filteredEvents);
    }, [events, colours, resourceToIds, enabledResources]);

    useEffect(() => {
        calendarRef?.current?.getApi().changeView(view)
    }, [view]);

    const handleResourceToggle = (resourceId: string) => {
        setEnabledResources(prevEnabled => {
            const newEnabled = new Set(prevEnabled);
            if (newEnabled.has(resourceId)) {
                newEnabled.delete(resourceId);
            } else {
                newEnabled.add(resourceId);
            }
            return newEnabled;
        });
    };

    const handleToggleAll = () => {
        if (enabledResources.size === resourceOptions.length) {
            setEnabledResources(new Set());
        } else {
            setEnabledResources(new Set(resourceOptions.map(option => option.value)));
        }
    };

    const handleAfterTransition = () => {
        if (resourcesHidden) {
            setResourcesContainerRendered(false);
        }
    };


    function renderEventContent(eventInfo: FCEventContentArg<FCallBaseEvent>) {
        const type = eventInfo.event.extendedProps.type;
        if(type === 'background') {
            return null;
        }
        if(type === 'temporary') {
            return <EventContentTemp eventInfo={eventInfo as FCEventContentArg<FCallTempEvent>}/>
        }
        return <EventContent eventInfo={eventInfo as FCEventContentArg<FCallEvent>}
        />
    }
    return (
        <CalendarProvider resourceToIds={resourceToIds} resources={props.resources} tempEvents={tempEvents} setTempEvents={setTempEvents}>
            <div className={`${styles.calendar} ${resourcesHidden ? styles.closed : ''} `}
                // onTransitionStart={handleBeforeTransition}
                 onTransitionEnd={handleAfterTransition}>
                <CalendarHeader view={view} calendarRef={calendarRef} setView={(v) => setView(v)}/>
                <CalendarResourceFilter
                    hidden={!resourcesContainerRendered}
                    resourceOptions={resourceOptions}
                    enabledResources={enabledResources}
                    onToggle={handleResourceToggle}
                    onToggleAll={handleToggleAll}
                />
                <CalendarInnerHeader view={view} resourcesHidden={resourcesHidden}
                                     setResourcesHidden={setResourcesHidden} setView={(v) => setView(v)}
                                     setLastCalendarView={() => setView(lastCalendarView)} building={props.building}/>
                <FullCalendar
                    ref={calendarRef}
                    plugins={[interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin]}
                    initialView={view}
                    slotMinTime={slotMinTime}
                    slotMaxTime={slotMaxTime}
                    headerToolbar={false}
                    slotDuration={"00:30:00"}
                    themeSystem={'bootstrap'}
                    firstDay={1}
                    eventClick={(clickInfo) => handleEventClick(clickInfo as any)}
                    datesSet={(dateInfo) => {
                        props.onDateChange(dateInfo);
                    }}
                    eventContent={(eventInfo: FCEventContentArg<FCallEvent | FCallTempEvent>) => renderEventContent(eventInfo)}
                    views={{
                        timeGrid: {
                            slotLabelFormat: {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            }
                        },
                        list: {
                            eventClassNames: ({event: {extendedProps}}) => {
                                return `clickable ${
                                    extendedProps.cancelled ? 'event-cancelled' : ''
                                }`
                            },
                        },
                        month: {
                            eventTimeFormat: {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            },
                        },
                    }}
                    dayHeaderFormat={{weekday: 'long'}}
                    dayHeaderContent={(args) => (
                        <div className={styles.dayHeader}>
                            <div>{args.date.toLocaleDateString('nb-NO', {weekday: 'long'})}</div>
                            <div>{args.date.getDate()}</div>
                        </div>
                    )}
                    weekNumbers={true}
                    weekText="Uke "
                    locale={DateTime.local().locale}
                    selectable={true}
                    height={'auto'}
                    eventMaxStack={4}
                    select={handleDateSelect}
                    dateClick={handleDateClick}
                    events={[...calendarEvents, ...tempEventArr, ...renderBackgroundEvents()] as EventInput[]}
                    // editable={true}
                    selectOverlap={(stillEvent, movingEvent) => stillEvent?.extendedProps?.type !=='event'}
                    eventResize={handleEventResize}
                    eventDrop={handleEventResize}
                    initialDate={currentDate.toJSDate()}
                    // style={{gridColumn: 2}}
                />

                <EventPopper
                    event={selectedEvent}
                    placement={
                        popperPlacement()
                    }
                    anchor={popperAnchorEl} onClose={() => {
                    setSelectedEvent(null);
                    setPopperAnchorEl(null);
                }}/>

            </div>
        </CalendarProvider>
    );
}

export default BuildingCalendar;
