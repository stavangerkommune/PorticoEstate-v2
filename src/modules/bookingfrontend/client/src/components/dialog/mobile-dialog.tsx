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


//
// interface DialogProps {
//     /** Boolean to control the visibility of the modal */
//     open: boolean;
//     /** Function to close the modal */
//     onClose: () => void;
// }
//
// /**
//  * MobileDialog Component
//  *
//  * This component renders a fullscreen modal that slides in from the bottom.
//  * It uses the `<dialog>` HTML element and SCSS modules for styling.
//  *
//  * @param open - Controls whether the modal is open or closed
//  * @param onClose - Callback function to close the modal
//  */
// const MobileDialog: React.FC<DialogProps> = ({ open, onClose }) => {
//     const dialogRef = useRef<HTMLDialogElement>(null);
//     const [show, setShow] = useState<boolean>(false);
//
//     // Handle body scroll and opening/closing animation
//     useEffect(() => {
//         const dialog = dialogRef.current;
//
//         if (open) {
//             // Show dialog and disable body scroll
//             if (dialog) {
//                 dialog.showModal();  // Show the <dialog>
//                 setTimeout(() => {
//                     // dialog.classList.add(styles.show);
//                     setShow(true); // Add animation class
//                 }, 10);  // Delay to ensure the transition takes effect
//             }
//             document.body.style.overflow = 'hidden';  // Disable body scroll
//         } else {
//             // Close dialog and enable body scroll
//             if (dialog) {
//                 // dialog.classList.remove(styles.show);
//                 setShow(false); // Remove animation class
//
//                 setTimeout(() => {
//                     dialog.close();  // Close the <dialog> after animation
//
//                 }, 300);  // Match the animation duration
//             }
//             document.body.style.overflow = 'auto';  // Enable body scroll
//         }
//
//         // Cleanup to ensure body scroll is restored if component unmounts
//         return () => {
//             document.body.style.overflow = 'auto';
//         };
//     }, [open]);
//
//     return (
//         <dialog ref={dialogRef} className={`${show ? styles.show : ''} ${styles.modal}`}>
//             <div className={styles.modalContent}>
//                 <h2>Fullscreen Modal</h2>
//                 <p>This is a fullscreen modal that slides in from the bottom.</p>
//                 <button onClick={onClose}>Close Modal</button>
//             </div>
//         </dialog>
//     );
// };
//
// export default MobileDialog;