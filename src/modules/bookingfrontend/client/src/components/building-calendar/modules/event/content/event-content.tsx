import React, {FC, useEffect, useRef, useState} from 'react';
import styles from './event-content.module.scss';
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faClock} from "@fortawesome/free-regular-svg-icons";
import {faLayerGroup, faUser, faUsers} from "@fortawesome/free-solid-svg-icons";
import {formatEventTime} from "@/service/util";
import {FCallEvent, FCEventContentArg} from "@/components/building-calendar/building-calendar.types";
import ColourCircle from "@/components/building-calendar/modules/colour-circle/colour-circle";
import {usePopperGlobalInfo} from "@/service/api/event-info";
import popperStyles from '../popper/event-popper.module.scss'

interface EventContentProps {
    eventInfo: FCEventContentArg<FCallEvent>;
}

const pxPerMinute = 0.93;
const mainBigContentHeight = 150;
const mediumContentHeight = 43;
const resourceItemHeight = 28;
const resourceItemGap = 4;

const EventContent: FC<EventContentProps> = (props) => {
    const {eventInfo} = props;
    const eventRef = useRef<HTMLDivElement | null>(null);
    const {
        data: infoData,
        isLoading
    } = usePopperGlobalInfo(props.eventInfo.event.extendedProps.type, props.eventInfo.event.id);


    const [visibleResources, setVisibleResources] = useState<number>(0);
    useEffect(() => {
        if (!['booking', 'allocation', 'event'].includes(eventInfo.event.extendedProps.type)) {
            return;
        }

        const calculateVisibleResources = () => {
            if (!eventRef.current) return;

            const duration = eventInfo.event.end!.getTime() - eventInfo.event.start!.getTime();
            const durationInMinutes = duration / (1000 * 60);
            const eventHeight = "offsetHeight" in eventRef.current ? eventRef.current.offsetHeight : 150;


            const availableHeight = eventHeight - mainBigContentHeight;
            const maxVisibleResources = Math.floor(availableHeight / (resourceItemHeight + resourceItemGap));

            setVisibleResources(Math.max(0, maxVisibleResources));
        };

        calculateVisibleResources();
        window.addEventListener('resize', calculateVisibleResources);

        return () => {
            window.removeEventListener('resize', calculateVisibleResources);
        };
    }, [eventInfo]);


    const duration = eventInfo.event.end!.getTime() - eventInfo.event.start!.getTime();
    const durationInMinutes = duration / (1000 * 60);
    const actualTimeText = formatEventTime(eventInfo.event);

    const renderColorCircles = (maxCircles: number, size: 'medium' | 'small') => {
        const resources = eventInfo.event.extendedProps.source.resources;
        const totalResources = resources.length;
        const circlesToShow = resources.slice(0, maxCircles);
        const remainingCount = totalResources - maxCircles;

        return (
            <div className={styles.colorCircles}>
                {circlesToShow.map((res, index) => (
                    <ColourCircle resourceId={res.id} key={index} className={styles.colorCircle} size={size}/>
                ))}
                {remainingCount === 1 && (
                    <ColourCircle
                        resourceId={resources[maxCircles].id}
                        key={maxCircles}
                        className={styles.colorCircle}
                        size={size}
                    />
                )}
                {remainingCount > 1 && <span className={styles.remainingCount}>+{remainingCount}</span>}
            </div>
        );
    };

    const renderResourceItems = () => {
        const totalResources = eventInfo.event.extendedProps.source.resources.length;
        const resourcesToShow = eventInfo.event.extendedProps.source.resources.slice(0, visibleResources);
        const remainingCount = totalResources - visibleResources;

        return (
            <>
                {resourcesToShow.map((resource, index) => (
                    <div key={index} className={`${popperStyles.resourceItem} ${popperStyles.gray}`}>
                        <ColourCircle resourceId={resource.id} size={'medium'}/>
                        <span className={popperStyles.resourceName}>{resource.name}</span>
                    </div>
                ))}
                {remainingCount === 1 && (
                    <div className={`${popperStyles.resourceItem} ${popperStyles.gray}`}>
                        <ColourCircle resourceId={eventInfo.event.extendedProps.source.resources[visibleResources].id}
                                      size={'medium'}/>
                        <span className={popperStyles.resourceName}>
                            {eventInfo.event.extendedProps.source.resources[visibleResources].name}
                        </span>
                    </div>
                )}
                {remainingCount > 1 && (
                    <div className={`${popperStyles.resourceItem} ${popperStyles.gray}`}>
                        <span className={popperStyles.resourceName}>+{remainingCount} more</span>
                    </div>
                )}
            </>
        );
    };

    let content;

    if (durationInMinutes <= 45) {
        // Short event: single line with time and up to 3 color circles (or 4 if there's just one more)
        content = (
            <div className={`${styles.event} ${styles.shortEvent}`}>
                <span className={`${styles.time} text-overline`}>
                    <FontAwesomeIcon className={'text-label'} icon={faClock}/>{actualTimeText}
                </span>
                {renderColorCircles(3, 'small')}
            </div>
        );
    } else {
        // Medium event: two lines, time on first line, title and up to 6 color circles (or 7 if there's just one more) on second
        content = (
            <div className={`${styles.event} ${durationInMinutes <= 90 ? styles.mediumEvent : styles.longEvent}`}>
                <span className={`${styles.time} text-overline`}>
                    <FontAwesomeIcon className={'text-label'} icon={faClock}/>{actualTimeText}
                </span>
                {durationInMinutes > 60 && (
                    <div className={styles.title}>{eventInfo.event.title}</div>
                )}
                {durationInMinutes > 120 && (
                    <div className={`text-small ${styles.orderNumber}`}># {eventInfo.event.id}</div>
                )}

                {durationInMinutes > 150 && infoData?.organizer && (
                    <div className={`text-small ${styles.organizer}`}>
                        <FontAwesomeIcon className={'text-small'} icon={faUser}/> {infoData?.organizer}
                    </div>
                )}
                {durationInMinutes > 150 && (infoData?.info_participant_limit || 0) > 0 && (
                    <div className={`text-small ${styles.participantLimit}`}>
                        <FontAwesomeIcon className={'text-small'} icon={faUsers}/>
                        <span>Max {infoData?.info_participant_limit} participants</span>
                    </div>
                )}
                {durationInMinutes < 120 && (
                        <div className={`${styles.resourceIcons} text-label`}>
                            <FontAwesomeIcon icon={faLayerGroup}/>
                            {renderColorCircles(6, 'medium')}
                        </div>
                    ) ||
                    (<div className={popperStyles.resourcesList}>
                        {renderResourceItems()}
                    </div>)
                }

            </div>
        );
    }

    return <div ref={eventRef} style={{maxWidth: '100%'}}>{content}</div>;
};

export default EventContent;
