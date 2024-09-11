'use client'
import React, { FC, useState, useCallback, PropsWithChildren, useContext } from 'react';

interface LoadingContextProps extends PropsWithChildren {}

type LoadingType = 'hard' | 'soft';

type LoadingContextType = {
    setLoadingState: (key: string, state: boolean, type?: LoadingType) => void;
    isHardLoading: boolean;
    isSoftLoading: boolean;
    hardLoadingStates: Map<string, boolean>;
    softLoadingStates: Map<string, boolean>;
}

const LoadingContextDefaults: LoadingContextType = {
    setLoadingState: () => {},
    isHardLoading: true,  // Default to true when no states are set
    isSoftLoading: false,
    hardLoadingStates: new Map(),
    softLoadingStates: new Map()
}

const LoadingContext = React.createContext<LoadingContextType>(LoadingContextDefaults);

export const useLoadingContext = () => {
    const context = useContext(LoadingContext);

    if (context === undefined) {
        throw new Error('useLoadingContext must be used within a LoadingProvider');
    }

    return context;
};

export const useIsAnythingLoading = () => {
    const ctx = useLoadingContext();
    return ctx.isHardLoading || ctx.isSoftLoading;
}

export const useIsHardLoading = () => {
    const ctx = useLoadingContext();
    return ctx.isHardLoading;
}

export const useIsSoftLoading = () => {
    const ctx = useLoadingContext();
    return ctx.isSoftLoading;
}

export const LoadingProvider: FC<LoadingContextProps> = (props) => {
    const [hardLoadingStates, setHardLoadingStates] = useState<Map<string, boolean>>(new Map());
    const [softLoadingStates, setSoftLoadingStates] = useState<Map<string, boolean>>(new Map());

    const setLoadingState = useCallback((key: string, state: boolean, type: LoadingType = 'soft') => {
        if (type === 'hard') {
            setHardLoadingStates(prev => {
                const newMap = new Map(prev);
                newMap.set(key, state);
                return newMap;
            });
        } else {
            setSoftLoadingStates(prev => new Map(prev.set(key, state)));
        }
    }, []);

    const isHardLoading = hardLoadingStates.size === 0 || Array.from(hardLoadingStates.values()).some(state => state);
    const isSoftLoading = Array.from(softLoadingStates.values()).some(state => state);

    const value = { setLoadingState, isHardLoading, isSoftLoading, softLoadingStates, hardLoadingStates };

    return (
        <LoadingContext.Provider value={value}>
            {props.children}
        </LoadingContext.Provider>
    );
}