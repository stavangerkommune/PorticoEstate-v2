import {IEvent} from "@/service/pecalendar.types";
import {DateTime} from "luxon";
import styles from "@/components/building-calendar/building-calender.module.scss";
import {FCallEvent} from "@/components/building-calendar/building-calendar.types";


export function FCallEventConverter(event: IEvent, colours: Array<string>, resourceToIds: Record<number, number>, enabledResources: Set<string>): FCallEvent | null {
    const is_public = 'is_public' in event ? event.is_public : 1;
    const resourceColours = event.resources
        .filter(resource => enabledResources.has(resource.id.toString()))
        // .map(a => colours[resourceToIds[a.id] % colours.length]);

    // If no enabled resources for this event, return null
    if (resourceColours.length === 0) return null;

    const startDateTime = DateTime.fromISO(`${event.date}T${event.from}`);
    const endDateTime = DateTime.fromISO(`${event.date}T${event.to}`);

    // Calculate the duration of the event in minutes
    const durationMinutes = endDateTime.diff(startDateTime, 'minutes').minutes;

    // If the duration is less than 30 minutes, extend the end time for display purposes
    const displayEndDateTime = durationMinutes < 30
        ? startDateTime.plus({ minutes: 30 })
        : endDateTime;


    return {
        id: event.id,
        title: (is_public === 1 ? event.name : 'Private Event') + ` \n${event.type}`,
        start: DateTime.fromISO(`${event.date}T${event.from}`).toJSDate(),
        end: displayEndDateTime.toJSDate(),
        className: [`${styles[`event-${event.type}`]} ${styles.event}`],
        extendedProps: {
            actualStart: startDateTime.toJSDate(),
            actualEnd: endDateTime.toJSDate(),
            isExtended: durationMinutes < 30,
            source: event,
            type: event.type
        },
    };
}
