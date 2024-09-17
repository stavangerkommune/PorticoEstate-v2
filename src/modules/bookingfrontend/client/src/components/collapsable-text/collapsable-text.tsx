import {FC, useState} from 'react';

interface CollapsableTextProps {
    text: string;
}

const CollapsableText: FC<CollapsableTextProps> = (props) => {
    const [expanded, setExpanded] = useState<boolean>(false);
    return (
        <div></div>
    );
}

export default CollapsableText


