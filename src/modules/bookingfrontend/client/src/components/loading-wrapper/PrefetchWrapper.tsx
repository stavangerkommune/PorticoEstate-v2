'use client'
import {FC, PropsWithChildren, useEffect} from 'react';
import {useBookingUser} from "@/service/hooks/api-hooks";
import {useLoadingContext} from "@/components/loading-wrapper/LoadingContext";

interface PrefetchWrapperProps extends PropsWithChildren {
}

const PrefetchWrapper: FC<PrefetchWrapperProps> = (props) => {
    const {isLoading: isBookingUserLoading} = useBookingUser();
    const {setLoadingState} = useLoadingContext();

    useEffect(() => {
        setLoadingState('isBookingUserLoading', isBookingUserLoading);
    }, [isBookingUserLoading,setLoadingState]);
    return props.children;
}

export default PrefetchWrapper


