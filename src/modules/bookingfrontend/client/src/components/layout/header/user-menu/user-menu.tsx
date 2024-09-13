'use client'
import {FC} from 'react';
import {useBookingUser} from "@/service/hooks/api-hooks";
import {Button, Divider, DropdownMenu} from "@digdir/designsystemet-react";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {faSignInAlt, faSignOutAlt, faUser} from "@fortawesome/free-solid-svg-icons";
import {useTrans} from "@/app/i18n/ClientTranslationProvider";
import {phpGWLink} from "@/service/util";
import Link from "next/link";

interface UserMenuProps {
}

const UserMenu: FC<UserMenuProps> = (props) => {
    const t = useTrans();
    const bookingUserQ = useBookingUser();
    const {data: bookingUser, isLoading} = bookingUserQ;

    if (bookingUser?.is_logged_in) {
        return (<DropdownMenu.Root
            placement="bottom-end"
            size="md"
        >
            <DropdownMenu.Trigger variant={'tertiary'} color={'neutral'} size={'sm'}>
                <FontAwesomeIcon icon={faUser}/> {bookingUser.orgnr}
            </DropdownMenu.Trigger>
            <DropdownMenu.Content>
                <DropdownMenu.Group>
                    <DropdownMenu.Item asChild>
                        <Link href={phpGWLink('bookingfrontend/', {menuaction: 'bookingfrontend.uiuser.show'}, false)}
                              className={'link-text link-text-unset normal'}>
                            {t('bookingfrontend.my page')}
                        </Link>
                    </DropdownMenu.Item>
                </DropdownMenu.Group>
                <Divider/>
                <DropdownMenu.Group>

                        <DropdownMenu.Item asChild>
                            <Link href={phpGWLink(['bookingfrontend', 'logout'])}
                                  className="link-text link-text-unset normal">
                            {t('common.logout')}
                            </Link>

                        </DropdownMenu.Item>

                </DropdownMenu.Group>
            </DropdownMenu.Content>
        </DropdownMenu.Root>);
    }

    return (
        <Link
            href={phpGWLink(['bookingfrontend', 'login/'], {after: encodeURI(window.location.href.split('bookingfrontend')[1])})}
            className={'link-text link-text-unset'}>
            <Button variant={'tertiary'} color={'neutral'} size={'sm'}>
                <FontAwesomeIcon icon={faSignInAlt}/> {t('common.login')}
            </Button>
        </Link>
    );
}

export default UserMenu


