import {QueryClient, useQuery, useQueryClient, UseQueryResult} from "@tanstack/react-query";
import {IBookingUser} from "@/service/types/api.types";
import {fetchBookingUser} from "@/service/api/api-utils";

export function useBookingUser(): UseQueryResult<IBookingUser> {
    return useQuery(
        {
            queryKey: ['bookingUser'],
            queryFn: () => fetchBookingUser(), // Fetch function
            retry: 2, // Number of retry attempts if the query fails
            refetchOnWindowFocus: false, // Do not refetch on window focus by default
        }
    );
}
