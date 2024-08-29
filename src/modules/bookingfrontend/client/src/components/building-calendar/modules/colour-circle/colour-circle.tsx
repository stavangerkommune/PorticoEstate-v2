import React, {FC} from 'react';
import {useResourceToId} from "@/components/building-calendar/calendar-context";
import {useColours} from "@/service/hooks/Colours";
import styles from './colour-circle.module.scss';

interface ColourCircleProps {
    resourceId: number;
    className?: string | undefined
    size?: 'small' | 'medium' | number;
}
const ColourCircle: FC<ColourCircleProps> = (props) => {
    const resourceToIds = useResourceToId();
    const colours = useColours();
    const colour = colours? colours[resourceToIds[props.resourceId] % colours.length] : 'gray';
    return (
        <span
            className={`${styles.resourceColorIndicator} ${typeof props.size === 'string' ? styles[props.size] : ''} ${props.className || ''}`}
            style={{backgroundColor: colour, ...(typeof props.size === "number" ? {width: props.size, height: props.size, minWidth: props.size} : {})}}
        />
    // <span style={{
    //     display: 'inline-block',
    //     width: '0.8rem',
    //     height: '0.8rem',
    //     borderRadius: '50%',
    //     backgroundColor: colour,
    //         marginLeft: '0.5rem',
    //     }}/>
    );
}

export default ColourCircle


