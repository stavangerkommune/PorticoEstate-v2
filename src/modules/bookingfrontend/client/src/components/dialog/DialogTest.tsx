'use client'
import {FC, useState} from 'react';
import MobileDialog from "@/components/dialog/mobile-dialog";
import LanguageSwitcher from "@/app/i18n/language-switcher";

interface DialogTestProps {
}

const DialogTest: FC<DialogTestProps> = (props) => {
    const [open, setOpen] = useState<boolean>(false);
    return (
        <LanguageSwitcher/>
    );
}

export default DialogTest


