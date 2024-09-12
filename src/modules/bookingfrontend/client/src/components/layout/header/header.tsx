import styles from './header.module.scss'
import {fetchServerSettings} from "@/service/api/api-utils";
import {phpGWLink} from "@/service/util";
import ClientPHPGWLink from "@/components/layout/header/ClientPHPGWLink";
import HeaderMenuContent from "@/components/layout/header/header-menu-content";
import DialogTest from "@/components/dialog/DialogTest";

interface HeaderProps {
}

const Header = async (props: HeaderProps) => {
    const serverSettings = await fetchServerSettings();
    const logoPath = "/phpgwapi/templates/bookingfrontend_2/styleguide/gfx";
    return (
        <nav className={styles.navbar}>
            <ClientPHPGWLink strURL={'bookingfrontend/'} className={styles.logo} >
                <img src={`${serverSettings.webserver_url}${logoPath}/logo_aktiv_kommune_horizontal.png`} alt="Aktiv kommune logo"
                     className={styles.logoImg}/>
                <img src={`${serverSettings.webserver_url}${logoPath}/logo_aktiv_kommune.png`} alt="Aktiv kommune logo"
                     className={`${styles.logoImg} ${styles.logoImgDesktop}`}/>
            </ClientPHPGWLink>

            <DialogTest />
            <HeaderMenuContent>


            </HeaderMenuContent>
        </nav>
    );
}

export default Header


