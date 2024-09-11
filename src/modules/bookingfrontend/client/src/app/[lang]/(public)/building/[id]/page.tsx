import {FC} from 'react';
interface BuildingShowParams {
    id: string;
}
interface BuildingShowProps {
    params: BuildingShowParams
}

const BuildingShow: FC<BuildingShowProps> = (props) => {
    return (
        <div>{props.params.id}</div>
    );
}

export default BuildingShow
