import { FC, Fragment, PropsWithChildren, useState } from 'react';
import { Chevron } from '../vectors/chevron';
import ExpandableContent from '../expandable-content/expandable-content';

type RowExpandProps = PropsWithChildren<{
    unCenter?: boolean;
}>

const RowExpand: FC<RowExpandProps> = (props) => {
    const [expanded, setExpanded] = useState<boolean>(false);
    return (
        <Fragment>
            <div className={'center-content'} style={{gridArea: 'expand',}}>
                <button
                    onClick={() => setExpanded(!expanded)}
                    style={{
                        padding: '1.2rem',
                    }}
                >
                    <Chevron open={expanded} />
                </button>
            </div>
            <ExpandableContent
                open={expanded}
                style={{
                    // gridColumn: '1/12',
                    gridArea: 'content'
                }}
            >
                {props.children}
            </ExpandableContent>
        </Fragment>
    );
};

export default RowExpand;
