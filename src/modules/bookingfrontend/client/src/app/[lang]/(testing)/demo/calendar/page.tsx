import {notFound} from 'next/navigation';
import BuildingCalendar from "@/components/building-calendar";

interface CalendarPageProps {
    searchParams: { building_id?: string };
}

export const dynamic = 'force-dynamic'

const CalendarPage = async ({searchParams}: CalendarPageProps) => {
    if (!searchParams.building_id) {
        return notFound();
    }
    return <BuildingCalendar building_id={searchParams.building_id}></BuildingCalendar>
};

export default CalendarPage;
