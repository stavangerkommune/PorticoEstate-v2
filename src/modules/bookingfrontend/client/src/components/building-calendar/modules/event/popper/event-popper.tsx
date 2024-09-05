import ClickAwayListener from "@mui/material/ClickAwayListener";
import {AppBar, Dialog, IconButton, Popper, Slide, Toolbar, Typography} from "@mui/material";
import {Placement} from "@popperjs/core";
import {FCallEvent, FCallTempEvent} from "@/components/building-calendar/building-calendar.types";
import TempEventPopperContent
    from "@/components/building-calendar/modules/event/popper/content/temp-event-popper-content";
import EventPopperContent from "@/components/building-calendar/modules/event/popper/content/event-popper-content";
import {FC, forwardRef, ReactElement, Ref, useEffect, useState} from "react";
import {phpGWLink} from "@/service/util";
import {TransitionProps} from "@mui/material/transitions";
import {CloseIcon} from "next/dist/client/components/react-dev-overlay/internal/icons/CloseIcon";
import DialogTransition from "@/components/dialog/DialogTransistion";
import MobileDialog from "@/components/dialog/mobile-dialog";
import styles from "@/components/dialog/mobile-dialog.module.scss";
import {Button, Tooltip} from "@digdir/designsystemet-react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faXmark} from "@fortawesome/free-solid-svg-icons";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";



interface EventPopperProps {
    event: FCallEvent | FCallTempEvent | null;
    onClose: () => void;
    anchor: HTMLElement | null;
    placement: Placement;
    isMobile: boolean;
}



const EventPopper: FC<EventPopperProps> = ({event, onClose, anchor, placement, isMobile}) => {
    const [open, setOpen] = useState(Boolean(event));
    const t = useTrans();
    useEffect(() => {
        setOpen(Boolean(event));
    }, [event]);

    if (!event) {
        return null;
    }
    const url = phpGWLink(
        ["bookingfrontend", 'buildings', 10],
        null,
        true,

    );
    console.log(url)


    const content = event.extendedProps.type === 'temporary' ? (
        <TempEventPopperContent event={event as FCallTempEvent} onClose={onClose} />
    ) : (
        <EventPopperContent event={event as FCallEvent} onClose={onClose} />
    );

    if (isMobile) {
        return (
            <MobileDialog open={open} onClose={onClose}>
                {/*<AppBar sx={{ position: 'relative' }}>*/}
                {/*    <Toolbar>*/}
                {/*        <IconButton*/}
                {/*            edge="start"*/}
                {/*            color="inherit"*/}
                {/*            onClick={onClose}*/}
                {/*            aria-label="close"*/}
                {/*        >*/}
                {/*            <CloseIcon />*/}
                {/*        </IconButton>*/}
                {/*        <Typography sx={{ ml: 2, flex: 1 }} variant="h6" component="div">*/}
                {/*            {event.title}*/}
                {/*        </Typography>*/}
                {/*    </Toolbar>*/}
                {/*</AppBar>*/}
                {/*<div style={{ padding: '16px' }}>*/}
                    {content}
                {/*</div>*/}
            </MobileDialog>
        );
    }


    return (
        <ClickAwayListener onClickAway={onClose}>
            <Popper open={Boolean(event)} anchorEl={anchor}
                    placement={placement}
                    style={{zIndex: 100}}>

                {content}
            </Popper>
        </ClickAwayListener>
    );
};

export default EventPopper;
