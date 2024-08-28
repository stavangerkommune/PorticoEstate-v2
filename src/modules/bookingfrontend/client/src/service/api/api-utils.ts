import {DateTime} from "luxon";
import {phpGWLink} from "@/service/util";

const BOOKING_MONTH_HORIZON = 2;


export async function fetchBuildingSchedule(building_id: number, dates: string[], instance?: string) {
    const url = phpGWLink('bookingfrontend/', {
        menuaction: 'bookingfrontend.uibooking.building_schedule_pe',
        building_id,
        dates: dates,
    }, true, instance);

    const response = await fetch(url);
    const result = await response.json();
    return result?.ResultSet?.Result?.results;
}


export async function fetchFreeTimeSlots(building_id: number, instance?: string) {
    const currDate = DateTime.fromJSDate(new Date());
    const maxEndDate = currDate.plus({ months: BOOKING_MONTH_HORIZON }).endOf('month');

    const url = phpGWLink('bookingfrontend/', {
        menuaction: 'bookingfrontend.uibooking.get_freetime',
        building_id,
        start_date: currDate.toFormat('dd/LL-yyyy'),
        end_date: maxEndDate.toFormat('dd/LL-yyyy'),
    }, true, instance);
    const response = await fetch(url);
    const result = await response.json();
    return result;
}
