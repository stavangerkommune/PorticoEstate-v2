import {FC} from 'react';
import BuildingCalendar from "@/components/building-calendar";
interface BuildingShowParams {
    id: string;
}
interface BuildingShowProps {
    params: BuildingShowParams
}

const BuildingShow: FC<BuildingShowProps> = (props) => {
    return (
        <BuildingCalendar building_id={props.params.id}/>
    );
}

export default BuildingShow
