import {phpGWLink} from "@/service/util";
import {useQuery} from "@tanstack/react-query";
import {IBuilding} from "@/service/types/Building";


export async function fetchBuilding(building_id: number, instance?: string): Promise<IBuilding> {
    const url = phpGWLink(
        ["bookingfrontend", 'buildings', building_id],
        null,
        true,
        instance
    );

    const response = await fetch(url);
    if (!response.ok) {
        throw new Error('Failed to fetch building data');
    }
    const result = await response.json();
    return result;
}

/**
 *  React hook to fetch building data.
 * @param {number} building_id - The ID of the building to fetch.
 * @param {string} instance - Optional instance string for the request.
 * @returns {object} - Returns the query object from TanStack Query.
 */
export function useBuilding(building_id: number, instance?: string) {
    return useQuery<IBuilding>(
        {
            queryKey: ['building', building_id],
            queryFn: () => fetchBuilding(building_id, instance), // Fetch function
            enabled: !!building_id, // Only run the query if building_id is provided
            retry: 2, // Number of retry attempts if the query fails
            refetchOnWindowFocus: false, // Do not refetch on window focus by default
        }
    );
}
