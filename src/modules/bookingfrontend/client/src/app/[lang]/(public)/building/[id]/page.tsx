import {FC} from 'react';
import BuildingCalendar from "@/components/building-calendar";
import {fetchBuilding, useBuilding} from "@/service/api/building";
import {notFound} from "next/navigation";
import BuildingHeader from "@/components/building-page/building-header";
interface BuildingShowParams {
    id: string;
}
interface BuildingShowProps {
    params: BuildingShowParams
}

const BuildingShow = async (props: BuildingShowProps) => {
    // Convert the id to a number
    const buildingId = parseInt(props.params.id, 10);

    // Check if the buildingId is a valid number
    if (isNaN(buildingId)) {
        // If not a valid number, throw the notFound error
        return notFound();
    }

    // Fetch the building
    const building = await fetchBuilding(buildingId);

    // If building does not exist, throw the notFound error
    if (!building) {
        return notFound();
    }
    return (
        <main>
            <BuildingHeader building={building} />
            <hr className={`my-2 mx-2`} />
            <BuildingCalendar building_id={props.params.id}/>
        </main>
    );
}

export default BuildingShow
