'use client'
import {AnchorHTMLAttributes, FC, PropsWithChildren} from 'react';
import Link from "next/link";
import {phpGWLink} from "@/service/util";

interface ClientPHPGWLinkProps extends PropsWithChildren, AnchorHTMLAttributes<HTMLAnchorElement> {
    strURL: string | (string | number)[];
    oArgs: Record<string, string | number | (string | number)[]> | null;
    bAsJSON?: boolean;
    baseURL?: string;


}

const ClientPHPGWLink: FC<ClientPHPGWLinkProps> = ({
                                                       strURL,
                                                       oArgs = {},
                                                       baseURL,
                                                       bAsJSON = true,
                                                       children,
                                                        ...props
                                                   }) => {
    // @ts-ignore
    return <Link href={phpGWLink(strURL, oArgs, bAsJSON, baseURL)} {...props}>{children}</Link>;
}

export default ClientPHPGWLink



