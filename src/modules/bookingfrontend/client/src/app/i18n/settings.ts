import { InitOptions } from 'i18next';
import {phpGWLink} from "@/service/util";

export const fallbackLng = 'no';
export const languages = [fallbackLng, 'en', 'nn'] as const;
export type LanguageType = typeof languages[number];

export const defaultNS = 'translation';
export const cookieName = 'i18next';

export function getOptions(lng: LanguageType = fallbackLng, ns: string | string[] = defaultNS): InitOptions {
    return {
        supportedLngs: languages,
        fallbackLng,
        lng,
        fallbackNS: defaultNS,
        defaultNS,
        ns,
        saveMissing: true, // Must be set to true
        parseMissingKeyHandler: (key: string) => {
            return `TRANSLATION MISSING FOR "${key}"`;
        }
    };
}

export const getTranslationURL = (lang: LanguageType): string => {
    return phpGWLink(["bookingfrontend", 'lang', lang], null, true);
};
