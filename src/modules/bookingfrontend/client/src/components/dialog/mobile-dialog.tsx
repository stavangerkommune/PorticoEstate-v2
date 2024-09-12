import {PropsWithChildren, useEffect, useRef, useState} from 'react';
import styles from "@/components/dialog/mobile-dialog.module.scss";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faXmark} from "@fortawesome/free-solid-svg-icons";
import {Button, Tooltip} from "@digdir/designsystemet-react";


interface DialogProps extends PropsWithChildren {
    /** Boolean to control the visibility of the modal */
    open: boolean;
    /** Function to close the modal */
    onClose: () => void;
}

/**
 * MobileDialog Component
 *
 * This component renders a fullscreen modal that slides in from the bottom.
 * It uses the `<dialog>` HTML element and SCSS modules for styling.
 *
 * @param open - Controls whether the modal is open or closed
 * @param onClose - Callback function to close the modal
 */
const MobileDialog: React.FC<DialogProps> = (props) => {
    const dialogRef = useRef<HTMLDialogElement | null>(null);
    const [show, setShow] = useState<boolean>(false);
    const t = useTrans();
    const [scrolled, setScrolled] = useState<boolean>(false);
    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) {
            return;
        }

        const onScroll = () => {
            setScrolled(dialog.scrollTop > 5)
        }
        dialog.addEventListener('scroll', onScroll);
        return () => {
            dialog.removeEventListener('scroll', onScroll); // Cleanup on unmount
        };
    }, [dialogRef.current]);

    // Handle body scroll and opening/closing animation
    useEffect(() => {
        const dialog = dialogRef.current;

        if (props.open) {
            // Show dialog and disable body scroll
            if (dialog) {
                dialog.showModal();  // Show the <dialog>
                setTimeout(() => {
                    // dialog.classList.add(styles.show);
                    setShow(true); // Add animation class
                }, 10);  // Delay to ensure the transition takes effect
            }
            document.body.style.overflow = 'hidden';  // Disable body scroll
        } else {
            // Close dialog and enable body scroll
            if (dialog) {
                // dialog.classList.remove(styles.show);
                setShow(false); // Remove animation class

                setTimeout(() => {
                    dialog.close();  // Close the <dialog> after animation

                }, 300);  // Match the animation duration
            }
            document.body.style.overflow = 'auto';  // Enable body scroll
        }

        // Cleanup to ensure body scroll is restored if component unmounts
        return () => {
            document.body.style.overflow = 'auto';
        };
    }, [props.open]);

    return (
        <dialog ref={dialogRef} className={`${show ? styles.show : ''} ${styles.modal}`}>
            <div className={styles.dialogContainer}>
                <div className={`${styles.dialogHeader} ${scrolled ? styles.scrolled : ''}`}>
                    <Tooltip content={t('booking.close')}>
                        <Button icon={true} variant='tertiary' aria-label='Tertiary med ikon'
                                onClick={props.onClose} className={'default'} size={'sm'}>
                            <FontAwesomeIcon icon={faXmark} size={'lg'}/>
                        </Button>
                    </Tooltip>
                </div>
                {props.children}
            </div>
        </dialog>
    );
};

export default MobileDialog;