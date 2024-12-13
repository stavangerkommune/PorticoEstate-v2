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
        return ids.filter(id => !queryClient.getQueryData([queryKey, id.toString()]));
    };

    // Filter out already cached ids
    const uncachedEventIds = filterCachedData(event_ids, 'eventInfo');
    const uncachedAllocationIds = filterCachedData(allocation_ids, 'allocationInfo');
    const uncachedBookingIds = filterCachedData(booking_ids, 'bookingInfo');

    // Fetch uncached data
    const fetchUncachedData = async () => {
        const fetchEvents = uncachedEventIds.length
            ? axios
                .get(phpGWLink('bookingfrontend/', {
                    menuaction: 'bookingfrontend.uievent.info_json',
                    ids: uncachedEventIds,
                }, true))
                .then(d => Object.values(d.data.events).map((e: any) => ({
                    info_user_can_delete_events: d.data.info_user_can_delete_events,
                    ...e,
                    type: 'event',
                })))
            : Promise.resolve([]);

        const fetchAllocations = uncachedAllocationIds.length
            ? axios
                .get(phpGWLink('bookingfrontend/', {
                    menuaction: 'bookingfrontend.uiallocation.info_json',
                    ids: uncachedAllocationIds,
                }, true))
                .then(d => Object.values(d.data.allocations).map((e: any) => ({
                    info_user_can_delete_allocations: d.data.user_can_delete_allocations,
                    ...e,
                    type: 'allocation',
                })))
            : Promise.resolve([]);

        const fetchBookings = uncachedBookingIds.length
            ? axios
                .get(phpGWLink('bookingfrontend/', {
                    menuaction: 'bookingfrontend.uibooking.info_json',
                    ids: uncachedBookingIds,
                }, true))
                .then(d => Object.values(d.data.bookings).map((e: any) => ({
                    info_user_can_delete_bookings: d.data.user_can_delete_bookings,
                    ...e,
                    type: 'booking',
                })))
            : Promise.resolve([]);

        // Execute all requests in parallel
        const results = await Promise.all([fetchEvents, fetchAllocations, fetchBookings]);

        // Flatten and filter fetched data
        const fetchedData = results.flat();

        // Cache newly fetched data
        fetchedData.forEach(data => {
            queryClient.setQueryData([`${data.type}Info`, data.id.toString()], data);
        });

        // Combine cached and fetched data for return
        return {
            event: event_ids.reduce((acc, id) => {
                const data = queryClient.getQueryData<FilteredEventInfo>(['eventInfo', id.toString()]);
                if (data) acc[id] = data;
                return acc;
            }, {} as Record<string, FilteredEventInfo>),
            allocation: allocation_ids.reduce((acc, id) => {
                const data = queryClient.getQueryData(['allocationInfo', id.toString()]);
                if (data) acc[id] = data;
                return acc;
            }, {} as Record<string, unknown>),
            booking: booking_ids.reduce((acc, id) => {
                const data = queryClient.getQueryData(['bookingInfo', id.toString()]);
                if (data) acc[id] = data;
                return acc;
            }, {} as Record<string, unknown>),
        };
    };

    return useQuery({
        queryKey: ['infos', ...event_ids, ...allocation_ids, ...booking_ids],
        queryFn: fetchUncachedData,
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


