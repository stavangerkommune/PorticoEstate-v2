import { createInstance, i18n } from 'i18next';
import { initReactI18next } from 'react-i18next/initReactI18next';
import {defaultNS, getOptions, getTranslationURL, LanguageType} from './settings';
import type {TFunction} from "i18next/typescript/t";

const initI18next = async (lng: LanguageType, ns?: string | string[]): Promise<i18n> => {
    const i18nInstance = createInstance();
    await i18nInstance
        .use(initReactI18next)
        .init(getOptions(lng, ns));

    // Fetch translations from the API
    const translationUrl = getTranslationURL(lng);
    console.log(translationUrl);
    const response = await fetch(translationUrl);
    const translations: Record<string, string> = await response.json();

    // Add the fetched resources to i18next
    i18nInstance.addResourceBundle(lng, Array.isArray(ns) ? ns[0] : ns, translations, true, true);

    return i18nInstance;
};

export async function getTranslation(lng: LanguageType, ns?: string | string[]): Promise<{t: (key: string) => string, i18n: i18n}> {
    const i18nextInstance = await initI18next(lng, ns || defaultNS);
    return {
        t: i18nextInstance.getFixedT(lng, Array.isArray(ns) ? ns[0] : ns) as TFunction,
        i18n: i18nextInstance
    };
}