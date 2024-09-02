'use client'
import {FC} from 'react';
import {LangPropsBaseType} from "@/app/[lang]/langPropsBase.types";
import {useClientTranslation} from "@/app/i18n/ClientTranslationProvider";

interface PageProps extends LangPropsBaseType {
}

const Page: FC<PageProps> = (props) => {
    const {t} = useClientTranslation()
    // const t = useTrans();
    return (
        <div>Hello lang!!!, {JSON.stringify(props.params)}
            {t('common.write here...')}
        </div>
    );
}

export default Page


