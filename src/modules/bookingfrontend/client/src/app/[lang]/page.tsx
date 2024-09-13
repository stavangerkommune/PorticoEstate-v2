'use client'
import {redirect} from "next/navigation";
import {RedirectType} from "next/dist/client/components/redirect";



const Page = () => {
    redirect('/info', RedirectType.replace)
}

export default Page


