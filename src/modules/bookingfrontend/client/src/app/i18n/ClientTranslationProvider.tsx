'use client'
import React, {createContext, useContext, useEffect, useState} from 'react';
import {getTranslation} from '@/app/i18n';
import {LanguageType} from '@/app/i18n/settings';
import {useLoadingContext} from "@/components/loading-wrapper/LoadingContext";

interface TranslationContextType {
    t: (key: string, options?: any) => string;
    i18n: any;
}

const TranslationContext = createContext<TranslationContextType | null>(null);

export const useClientTranslation = () => {
    const context = useContext(TranslationContext);
    if (!context) {
        throw new Error('useClientTranslation must be used within a TranslationProvider');
    }
    return context;
};
export const useTrans = () => {
    const context = useClientTranslation();
    return context.t;
};

interface TranslationProviderProps {
    children: React.ReactNode;
    lang: LanguageType;
}

const TranslationProvider: React.FC<TranslationProviderProps> = ({children, lang}) => {
    const {setLoadingState} = useLoadingContext();
    const [translationContext, setTranslationContext] = useState<TranslationContextType | null>(null);
    useEffect(() => {
        setLoadingState('translation', true, 'hard')
    }, [setLoadingState]);
    useEffect(() => {
        console.trace("translation provider Eff", new Date().getTime());
        const loadTranslations = async () => {
            const {t, i18n} = await getTranslation(lang);
            setTranslationContext({t, i18n});
            setLoadingState('translation', false, 'hard')
        };

        loadTranslations();
    }, [lang, setLoadingState]);


    return (
        <TranslationContext.Provider value={translationContext}>
            {children}
        </TranslationContext.Provider>
    );
};

export default TranslationProvider;