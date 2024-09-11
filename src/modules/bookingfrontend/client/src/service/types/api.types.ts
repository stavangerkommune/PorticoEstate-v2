export interface IServerSettings {
    account_max_id?: number;
    account_min_id?: number;
    account_repository?: string;
    acl_default?: string;
    addressmaster?: number;
    asyncservice?: string;
    auth_type?: string;
    auto_create_expire?: number;
    bakcground_image?: string;
    block_time?: number;
    cache_refresh_token?: number;
    cookie_domain?: string;
    daytime_port?: string;
    Debugoutput?: string;
    disable_autoload_langfiles?: boolean;
    encryption_type?: string;
    encryptkey?: string;
    file_repository?: string;
    files_dir?: string;
    file_store_contents?: string;
    group_max_id?: number;
    group_min_id?: number;
    hostname?: string;
    install_id?: string;
    lang_ctimes?: Record<string, unknown>;
    ldap_account_home?: string;
    ldap_account_shell?: string;
    ldap_encryption_type?: string;
    ldap_host?: string;
    ldap_root_pw?: string;
    log_levels?: Record<string, unknown>;
    logo_url: string;
    logo_title: string;
    mapping?: string;
    max_access_log_age?: number;
    max_history?: number;
    mcrypt_algo?: string;
    mcrypt_mode?: string;
    num_unsuccessful_id?: number;
    num_unsuccessful_ip?: number;
    password_level?: string;
    sessions_app_timeout?: number;
    sessions_timeout?: number;
    showpoweredbyon?: string;
    site_title?: string;
    SMTPDebug?: number;
    smtp_server?: string;
    support_address?: string;
    temp_dir?: string;
    usecookies?: boolean;
    useframes?: string;
    webserver_url?: string;
}

// User types
export interface IBookingUser {
    ssn: string | null;
    orgnr: string | null;
    orgname: string | null;
    org_id: number | null;
    is_logged_in: boolean;
}
