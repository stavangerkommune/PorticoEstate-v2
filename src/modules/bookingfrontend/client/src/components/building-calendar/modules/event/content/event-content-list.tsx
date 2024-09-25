import React, {FC} from 'react';
import styles from './event-content.module.scss';
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faClock, faUser, faLayerGroup} from "@fortawesome/free-solid-svg-icons";
import {formatEventTime} from "@/service/util";
import {FCallEvent, FCEventContentArg} from "@/components/building-calendar/building-calendar.types";
import ColourCircle from "@/components/building-calendar/modules/colour-circle/colour-circle";
import {usePopperGlobalInfo} from "@/service/api/event-info";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";
import {useIsMobile} from "@/service/hooks/is-mobile";

interface EventContentListProps {
    eventInfo: FCEventContentArg<FCallEvent>;
}

const EventContentList: FC<EventContentListProps> = ({eventInfo}) => {
    const t = useTrans();
    const isMobile = useIsMobile();
    const {data: infoData} = usePopperGlobalInfo(eventInfo.event.extendedProps.type, eventInfo.event.id);

    const actualTimeText = formatEventTime(eventInfo.event);

    const renderColorCircles = (maxCircles: number) => {
        const resources = eventInfo.event.extendedProps.source.resources;
        const totalResources = resources.length;
        const circlesToShow = resources.slice(0, maxCircles);
        const remainingCount = totalResources - maxCircles;

        return (
            <div className={styles.colorCircles}>
                {circlesToShow.map((res, index) => (
                    <ColourCircle resourceId={res.id} key={index} className={styles.colorCircle} size="small"/>
                ))}
                {remainingCount > 0 && <span className={styles.remainingCount}>+{remainingCount}</span>}
            </div>
        );
    };


    return (
        <div className={`${styles.event} ${styles.listEvent}`}>
              <span className={`${styles.time} text-overline`}>
                <FontAwesomeIcon className="text-label" icon={faClock}/>{actualTimeText}
              </span>
            <div className={styles.title}>{eventInfo.event.title}</div>
            <div className={`${styles.resourceIcons} text-label`}>
                <FontAwesomeIcon icon={faLayerGroup}/>
                {renderColorCircles(isMobile ? 1 : 3)}
            </div>
            {infoData?.organizer || !isMobile && (
                <div className={`text-small ${styles.organizer}`}>
                    <FontAwesomeIcon className="text-small"
                                     icon={faUser}/> {infoData?.organizer || t('bookingfrontend.not_available')}
                </div>)}
        </div>
    );
};

export default EventContentList;