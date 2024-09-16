import {createInstance, i18n} from 'i18next';
import {initReactI18next} from 'react-i18next/initReactI18next';
import {cookieName, defaultNS, fallbackLng, getOptions, getTranslationURL, ILanguage, languages} from './settings';

const initI18next = async (lng: ILanguage, ns?: string | string[]): Promise<i18n> => {
    const i18nInstance = createInstance();
    await i18nInstance
        .use(initReactI18next)
        .init(getOptions(lng, ns));

    // Fetch translations from the API
    const translationUrl = getTranslationURL(lng);
    const response = await fetch(translationUrl);
    const translations: Record<string, string> = await response.json();

    // Add the fetched resources to i18next
    i18nInstance.addResourceBundle(lng.key, (Array.isArray(ns) ? ns[0] : ns) as any, translations, true, true);

    return i18nInstance;
};

export async function getTranslation(lng?: string, ns?: string | string[]): Promise<{
    t: (key: string) => string,
    i18n: i18n
}> {
    let choosenLngString = lng;

    if(!choosenLngString && typeof window === 'undefined') {

        const cookies = require("next/headers").cookies
        const cookieStore = cookies();
        choosenLngString = cookieStore.get(cookieName as any)?.value
    }


    let language = languages.find(e => e.key === choosenLngString);

    if(!language) {
        language = fallbackLng;
    }
    const i18nextInstance = await initI18next(language, ns || defaultNS);
    return {
        t: i18nextInstance.getFixedT(language.key as any, (Array.isArray(ns) ? ns[0] : ns) as any),
        i18n: i18nextInstance
    };
}