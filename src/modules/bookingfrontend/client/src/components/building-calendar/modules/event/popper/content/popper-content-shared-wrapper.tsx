import {FC, PropsWithChildren} from 'react';
import {Button, Tooltip} from "@digdir/designsystemet-react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faXmark} from "@fortawesome/free-solid-svg-icons";
import styles from '../event-popper.module.scss';
import {useTrans} from "@/app/i18n/ClientTranslationProvider";

interface PopperContentSharedProps extends PropsWithChildren {
    onClose: () => void;

}

const PopperContentSharedWrapper: FC<PopperContentSharedProps> = (props) => {
    const t = useTrans();
    return (
        <div className={styles.eventPopper}>

            {props.children}
            {/*<div className={styles.eventPopperFooter}>*/}
            {/*    <Button onClick={props.onClose} variant="tertiary" className={'default'} size={'sm'}>*/}
            {/*        {t('common.ok').toLowerCase()}*/}
            {/*    </Button>*/}
            {/*</div>*/}
        </div>

    );
}

export default PopperContentSharedWrapper

