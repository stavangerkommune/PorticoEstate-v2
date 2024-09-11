import styles from './footer.module.scss'
import {fetchServerSettings} from "@/service/api/api-utils";
import {getTranslation} from "@/app/i18n";
import Link from "next/link";
import FooterUser from "@/components/layout/footer/footer-user";
import {LanguageType} from "@/app/i18n/settings";

interface FooterProps {
    lang: LanguageType
}

const Footer = async (props: FooterProps) => {
    const {t} = await getTranslation(props.lang)
    const serverSettings = await fetchServerSettings();
    console.log(serverSettings)
    return (
        <footer className={styles.footerContainer}>
            <div className={styles.footerLogoContainer}>
                {/*  LOGO */}
                <img
                    className={styles.footerLogo}
                    src={serverSettings.logo_url} alt={serverSettings.logo_title}
                />
            </div>
            <div>
                {/*Contact */}
                <h3 className={'text-body'}>
                    {t('common.contact')}
                </h3>
                <ul className={'list-unstyled text-small'}>
                    <li>
                        <span>TODO: Fix denne eposten:</span>
                        <span>{'$config_backend\t = CreateObject(\'phpgwapi.config\', \'booking\')->read();'}</span>

                    </li>
                    <li>
                        <Link href={`mailto:${serverSettings.support_address}`} target="_blank"
                              rel="noopener noreferrer"  className="link-text link-text-secondary normal">
                            {serverSettings.support_address}
                        </Link>
                    </li>
                    <li>
                        <Link href="https://github.com/PorticoEstate/-Aktiv-Kommune-feil-forslag/issues" target="_blank"
                              rel="noopener noreferrer"  className="link-text link-text-secondary normal">
                            {t('common.error_report_system')}
                        </Link>
                    </li>
                </ul>
            </div>
            <div>
                {/*About*/}
                <h3 className={'text-body'}>
                    Aktiv kommune
                </h3>
                <ul className={'list-unstyled text-small'}>
                    <li>
                        <Link href="https://www.aktiv-kommune.no/" target="_blank" rel="noopener noreferrer" className="link-text link-text-secondary normal">
                            {t('bookingfrontend.about aktive kommune')}
                        </Link>
                    </li>
                    <li>
                        <Link href="https://www.aktiv-kommune.no/manual/" target="_blank" rel="noopener noreferrer"  className="link-text link-text-secondary normal">
                            {t('common.manual')}
                        </Link>
                    </li>
                    <li>
                        <Link href={"https://www.aktiv-kommune.no/hva-er-aktivkommune/"} target="_blank"
                              rel="noopener noreferrer"  className="link-text link-text-secondary normal">
                            {t('bookingfrontend.privacy')}
                        </Link>
                    </li>
                </ul>

            </div>
            <div>
                {/*User*/}
                <h3 className={'text-body'}>
                    {t('common.login')}
                </h3>
                <FooterUser />

            </div>
        </footer>
    );
}

export default Footer


