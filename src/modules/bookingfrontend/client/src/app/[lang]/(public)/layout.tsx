import {ILanguage} from "@/app/i18n/settings";

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

interface PublicLayoutProps extends PropsWithChildren {
    params: {
        lang: string
    }

}

const PublicLayout: FC<PublicLayoutProps> = (props) => {
    return (
        <LoadingProvider>
            <ClientTranslationProvider lang={props.params.lang}>
                <ReactQueryProvider>
                    <PrefetchWrapper>
                        <LoadingIndicationWrapper>
                            <Header />
                            {props.children}
                            <Footer lang={props.params.lang}/>
                        </LoadingIndicationWrapper>
                    </PrefetchWrapper>
                </ReactQueryProvider>
            </ClientTranslationProvider>
        </LoadingProvider>
    );
}
export default PublicLayout