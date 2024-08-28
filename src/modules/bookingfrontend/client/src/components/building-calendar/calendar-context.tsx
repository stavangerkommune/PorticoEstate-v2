import {createContext, FC, PropsWithChildren, useContext} from 'react';
import {IBuildingResource} from "@/service/pecalendar.types";
import {FCallTempEvent} from "@/components/building-calendar/building-calendar.types";


interface CalendarContextType {
    resourceToIds: { [p: number]: number };
    resources: Record<string, IBuildingResource>;
    tempEvents: Record<string, FCallTempEvent>;
    setTempEvents: (value: (((prevState: Record<string, FCallTempEvent>) => Record<string, FCallTempEvent>) | Record<string, FCallTempEvent>)) => void

}


const CalendarContext = createContext<CalendarContextType | undefined>(undefined);


export const useResourceToId = () => {
    const ctx = useCalendarContext();
    return ctx.resourceToIds;
}


export const useTempEvents = () => {
    const ctx = useCalendarContext();
    return {tempEvents: ctx.tempEvents, setTempEvents: ctx.setTempEvents};
}

export const useAvailableResources = () => {
    const ctx = useCalendarContext();
    return ctx.resources;
}
export const useCalendarContext = () => {
    const context = useContext(CalendarContext);
    if (context === undefined) {
        throw new Error('useCalendarContext must be used within a CalendarProvider');
    }
    return context;
};

interface CalendarContextProps extends CalendarContextType{
    // resourceToIds: { [p: number]: number };
    // resources: Record<string, IBuildingResource>;

}

const CalendarProvider: FC<PropsWithChildren<CalendarContextProps>> = (props) => {
    return (
        <CalendarContext.Provider value={{resourceToIds: props.resourceToIds, resources: props.resources, setTempEvents: props.setTempEvents, tempEvents: props.tempEvents}}>
            {props.children}
        </CalendarContext.Provider>
    );
}

export default CalendarProvider


