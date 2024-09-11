import {dir} from 'i18next'
import {languages, LanguageType} from "@/app/i18n/settings";

import type {Metadata} from "next";
import {Roboto} from "next/font/google";
import '@digdir/designsystemet-css';
import '@digdir/designsystemet-theme';
import "@/app/globals.scss";
import ReactQueryProvider from "@/app/providers";
import {FC, PropsWithChildren} from "react";
import ClientTranslationProvider from "@/app/i18n/ClientTranslationProvider";
import {LoadingProvider} from "@/components/loading-wrapper/LoadingContext";
import LoadingIndicationWrapper from "@/components/loading-wrapper/LoadingIndicationWrapper";
import Footer from "@/components/layout/footer/footer";
import Header from "@/components/layout/header/header";
import PrefetchWrapper from "@/components/loading-wrapper/PrefetchWrapper";

interface RootLayoutProps extends PropsWithChildren {
    params: {
        lang: LanguageType
    }

}

const RootLayout: FC<RootLayoutProps> = (props) => {

    return (

        <LoadingProvider>
            <ClientTranslationProvider lang={props.params.lang as LanguageType}>
                <ReactQueryProvider>
                    <PrefetchWrapper>
                        <LoadingIndicationWrapper>
                            <Header></Header>
                            {props.children}
                            <Footer lang={props.params.lang}></Footer>
                        </LoadingIndicationWrapper>
                    </PrefetchWrapper>
                </ReactQueryProvider>
            </ClientTranslationProvider>
        </LoadingProvider>
    );
}
export default RootLayout