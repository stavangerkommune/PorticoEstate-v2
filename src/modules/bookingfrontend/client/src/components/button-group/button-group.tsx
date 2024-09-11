import {FC, PropsWithChildren} from 'react';
import styles from './button-group.module.scss'

interface ButtonGroupProps extends PropsWithChildren {
    className?: string | undefined;
}

const ButtonGroup: FC<ButtonGroupProps> = (props) => {
    return (
        <div className={`${styles.buttonGroup} ${props.className || ''}`}>
            {props.children}
        </div>
    );
}

export default ButtonGroup


