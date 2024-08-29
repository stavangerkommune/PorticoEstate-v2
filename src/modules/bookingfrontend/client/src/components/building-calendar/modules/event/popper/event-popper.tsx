import ClickAwayListener from "@mui/material/ClickAwayListener";
import {Popper} from "@mui/material";
import {Placement} from "@popperjs/core";
import {FCallEvent, FCallTempEvent} from "@/components/building-calendar/building-calendar.types";
import TempEventPopperContent
    from "@/components/building-calendar/modules/event/popper/content/temp-event-popper-content";
import EventPopperContent from "@/components/building-calendar/modules/event/popper/content/event-popper-content";
import {FC} from "react";

interface EventPopperProps {
    event: FCallEvent | FCallTempEvent | null;
    onClose: () => void;
    anchor: HTMLElement | null;
    placement: Placement
}

const EventPopper: FC<EventPopperProps> = ({event, onClose, anchor, placement}) => {
    if (!anchor) {
        return null;
    }


    return (
        <ClickAwayListener onClickAway={onClose}>
            <Popper open={Boolean(event)} anchorEl={anchor}
                    placement={placement}
                    style={{zIndex: 100}}>
                {event&& event.extendedProps.type === 'temporary' &&
                    <TempEventPopperContent event={event as FCallTempEvent} onClose={onClose}/> ||
                    <EventPopperContent event={event as FCallEvent} onClose={onClose}/>}

            </Popper>
        </ClickAwayListener>
    );
};

export default EventPopper;
