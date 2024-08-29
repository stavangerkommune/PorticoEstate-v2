import {useQuery, useQueryClient, UseQueryResult} from "@tanstack/react-query";
import {phpGWLink} from "@/service/util";
import axios from "axios";


export type PopperInfoType = FilteredEventInfo;

interface OrgInfo {
    customer_organization_id?: number;
    customer_organization_name?: string;
    org_link?: string;
}

export interface FilteredEventInfo {
    id: number;
    building_name: string;
    from_: string;
    to_: string;
    is_public: number;
    activity_name: string;
    resources: number[];

    // Fields for public events
    description?: string;
    organizer?: string;
    homepage?: string;
    name?: string;

    // Additional fields added after filtering
    type: 'event';
    info_resource_info: string;
    info_org: OrgInfo; // Now using the specific OrgInfo interface
    info_when: string;
    info_participant_limit: number;
    info_edit_link: string | null;
    info_cancel_link: string | null;
    info_ical_link: string;
    info_show_link: string;

    // Client added field
    info_user_can_delete_events: number;
}

type EventInfoUnion = FilteredEventInfo/* | unknown | unknown*/;

interface PopperData {
    event: Record<string, FilteredEventInfo>;
    allocation: unknown;
    booking: unknown;
}

export const usePopperData = (
    event_ids: (string | number)[],
    allocation_ids: (string | number)[],
    booking_ids: (string | number)[]
) => {
    const queryClient = useQueryClient();

    // Helper function to filter out cached data
    const filterCachedData = (ids: (string | number)[], queryKey: string) => {
        return ids.filter(id => !queryClient.getQueryData([queryKey, id]));
    };

    // Filter out already cached ids
    const uncachedEventIds = filterCachedData(event_ids, 'eventInfo');
    const uncachedAllocationIds = filterCachedData(allocation_ids, 'allocationInfo');
    const uncachedBookingIds = filterCachedData(booking_ids, 'bookingInfo');

    // Fetch uncached data
    const fetchUncachedData = async () => {
        const fetchEvents: Promise<unknown[] | FilteredEventInfo[]> | undefined = uncachedEventIds.length > 0 ?
            axios.get(phpGWLink('bookingfrontend/', {
                menuaction: 'bookingfrontend.uievent.info_json',
                ids: uncachedEventIds.map(id => id),
            }, true)).then(d => Object.values(d.data.events).map((e: any) => ({
                info_user_can_delete_events: d.data.info_user_can_delete_events, ...e,
                type: 'event'
            }))) : undefined;

        const fetchAllocations = uncachedAllocationIds.length > 0 ?
            axios.get(phpGWLink('bookingfrontend/', {
                menuaction: 'bookingfrontend.uiallocation.info_json',
                ids: uncachedAllocationIds.map(id => id),
            }, true)).then(d => Object.values(d.data.allocations).map((e: any) => ({
                info_user_can_delete_allocations: d.data.user_can_delete_allocations, ...e,
                type: 'allocation'
            }))) : undefined;


        const fetchBookings = uncachedBookingIds.length > 0 ?
            axios.get(phpGWLink('bookingfrontend/', {
                menuaction: 'bookingfrontend.uibooking.info_json',
                ids: uncachedBookingIds.map(id => id),
            }, true)).then(d => Object.values(d.data.bookings).map((e: any) => ({
                info_user_can_delete_bookings: d.data.user_can_delete_bookings, ...e,
                type: 'booking'
            }))) : undefined;


        // Execute all requests in parallel
        const results = await Promise.all([
            uncachedEventIds.length > 0 && fetchEvents || undefined,
            uncachedAllocationIds.length > 0 && fetchAllocations || undefined,
            uncachedBookingIds.length > 0 && fetchBookings || undefined
        ]);

        const resultData = results.filter(a => a).flatMap(a => a);
        // Cache the newly fetched data
        resultData.forEach((data: any, index) => {
            queryClient.setQueryData([data.type + 'Info', data.id.toString()], data, {});
        });

        // Return all data (cached + newly fetched)
        const returnV: PopperData = {
            event: event_ids.reduce((acc: Record<string, FilteredEventInfo>, id) => {
                acc[id] = queryClient.getQueryData<FilteredEventInfo>(['eventInfo', id.toString()])!;
                return acc;
            }, {}),
            allocation: allocation_ids.reduce((acc: Record<string, unknown>, id) => {
                acc[id] = queryClient.getQueryData(['allocationInfo', id.toString()]);
                return acc;
            }, {}),
            booking: booking_ids.reduce((acc: Record<string, unknown>, id) => {
                acc[id] = queryClient.getQueryData(['bookingInfo', id.toString()]);
                return acc;
            }, {}),
        };

        return returnV
    };

    return useQuery({
        queryKey: ['infos', ...event_ids, ...allocation_ids, ...booking_ids],
        queryFn: fetchUncachedData,
        // staleTime: 1000 * 60 * 5, // 5 minutes
        // cacheTime: 1000 * 60 * 10, // 10 minutes
    });
};
export const useEventPopperData = (event_id: (string | number)) => {
    const query = useQuery({
        queryKey: ['eventInfo', event_id],
        queryFn: () => {
            const url = phpGWLink('bookingfrontend/', {
                menuaction: 'bookingfrontend.uievent.info_json',
                id: event_id,
            }, true)

            return axios.get(url).then<FilteredEventInfo>(d => ({
                info_user_can_delete_events: d.data.info_user_can_delete_events, ...d.data.events[event_id],
                type: 'event'
            })) ;
        }
    })

    return query;
}


export const useAllocationPopperData = (allocation_id: (string | number)) => {
    const query = useQuery({
        queryKey: ['allocationInfo', allocation_id],
        queryFn: () => {
            const url = phpGWLink('bookingfrontend/', {
                menuaction: 'bookingfrontend.uiallocation.info_json',
                id: allocation_id,
            }, true)

            return axios.get(url).then(d => ({
                user_can_delete_allocations: d.data.user_can_delete_allocations, ...d.data.allocations[allocation_id],
                type: 'allocation'
            }));
        }
    })
    return query;
}


export const useBookingPopperData = (booking_id: (string | number)) => {
    const query = useQuery({
        queryKey: ['bookingInfo', booking_id],
        queryFn: () => {
            const url = phpGWLink('bookingfrontend/', {
                menuaction: 'bookingfrontend.uibooking.info_json',
                id: booking_id,
            }, true)

            return axios.get(url).then(d => ({
                user_can_delete_bookings: d.data.user_can_delete_bookings, ...d.data.bookings[booking_id],
                type: 'booking'
            }));
        }
    })
    return query;
}

export const usePopperGlobalInfo = (type: 'event' | 'allocation' | 'booking', id: string | number): UseQueryResult<EventInfoUnion> => {
    return useQuery({
        queryKey: [`${type}Info`, id.toString()],
        queryFn: async (): Promise<EventInfoUnion> => {
            let url: string;
            let dataKey: string;
            let permissionKey: string;

            switch (type) {
                case 'event':
                    url = phpGWLink('bookingfrontend/', {
                        menuaction: 'bookingfrontend.uievent.info_json',
                        id: id,
                    }, true);
                    dataKey = 'events';
                    permissionKey = 'info_user_can_delete_events';
                    break;
                case 'allocation':
                    url = phpGWLink('bookingfrontend/', {
                        menuaction: 'bookingfrontend.uiallocation.info_json',
                        id: id,
                    }, true);
                    dataKey = 'allocations';
                    permissionKey = 'user_can_delete_allocations';
                    break;
                case 'booking':
                    url = phpGWLink('bookingfrontend/', {
                        menuaction: 'bookingfrontend.uibooking.info_json',
                        id: id,
                    }, true);
                    dataKey = 'bookings';
                    permissionKey = 'user_can_delete_bookings';
                    break;
                default:
                    throw new Error(`Unsupported event type: ${type}`);
            }

            const response = await axios.get(url);
            const data = response.data[dataKey][id];
            const permission = response.data[permissionKey];

            return {
                ...data,
                [permissionKey]: permission,
                type: type
            } as EventInfoUnion;
        },
    });
};


