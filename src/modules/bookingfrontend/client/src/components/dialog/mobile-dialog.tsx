import {FC, PropsWithChildren, useEffect, useRef, useState} from 'react';
import DialogTransition from "@/components/dialog/DialogTransistion";
import {Dialog} from "@mui/material";
import styles from "@/components/dialog/mobile-dialog.module.scss";
import {Button, Tooltip} from "@digdir/designsystemet-react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faXmark} from "@fortawesome/free-solid-svg-icons";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";

interface DialogProps extends PropsWithChildren {
    open: boolean;
    onClose: () => void;
}

const MobileDialog: FC<DialogProps> = (props) => {
    const t = useTrans();
    const [scrolled, setScrolled] = useState<boolean>(false);
    const modalRef = useRef<HTMLDivElement | null>(null)


    useEffect(() => {
        if (!modalRef.current) {
            return;
        }
        const child = modalRef.current!.getElementsByClassName(styles.dialogContainer)?.[0]
        if (!child) {
            return;
        }
        const onScroll = () => {
            setScrolled(child.scrollTop > 5)
            // console.log(child.scrollTop)
        }
        // console.log(child)
        child.addEventListener('scroll', onScroll); // Add scroll listener
        return () => {
            child.removeEventListener('scroll', onScroll); // Cleanup on unmount
        };
    }, [modalRef.current]);
    return (
        <Dialog
            ref={modalRef}
            fullScreen
            open={props.open}
            onClose={props.onClose}
            TransitionComponent={DialogTransition}
            classes={{paperFullScreen: styles.dialogContainer}}
        >
            <div className={`${styles.dialogHeader} ${scrolled ? styles.scrolled : ''}`}>
                <Tooltip content={t('booking.close')}>
                    <Button icon={true} color='second' variant='tertiary' aria-label='Tertiary med ikon'
                            onClick={props.onClose} className={'default'} size={'sm'}>
                        <FontAwesomeIcon icon={faXmark} size={'lg'}/>
                    </Button>
                </Tooltip>
            </div>
            {props.children}

        </Dialog>
    );
}

export default MobileDialog


