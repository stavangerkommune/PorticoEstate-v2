import {NextRequest, NextResponse} from 'next/server';
import acceptLanguage from 'accept-language';
import {fallbackLng, languages, cookieName} from './app/i18n/settings';

acceptLanguage.languages(languages);

// Retrieve basePath from environment variables
const basePath = process.env.NEXT_PUBLIC_BASE_PATH || ''; // Default to empty string if not set

// export const config = {
//     // Adjust matcher to include basePath dynamically
//     matcher: [
//         `${basePath}/((?!api|_next/static|_next/image|img/|favicon.ico).*)`,
//         // Add other exclusions here if needed
//     ],};

export function middleware(req: NextRequest): NextResponse {
    // console.log(config.matcher, req.url, basePath)
    const acutalPath = req.nextUrl.pathname.split(basePath);
    const re = new RegExp("\/((api|_next\/static|_next\/image|img\/|favicon.ico).*)")
    if (acutalPath?.length > 0 && re.test(acutalPath[0])) {
        // console.log("YOHO diiddly ho", req.nextUrl.pathname.split(basePath))
        return;
    }


    let lng: string | undefined;
    // Determine the domain using proxy-aware logic
    const forwardedHost = req.headers.get('x-forwarded-host');
    const domain = forwardedHost || req.headers.get('host') || req.nextUrl.hostname;
    // Extract the language from the cookie if it exists
    if (req.cookies.has(cookieName)) {
        const cookieValue = req.cookies.get(cookieName)?.value;
        if (cookieValue) {
            lng = acceptLanguage.get(cookieValue);
        }
    }

    // Fallback to the Accept-Language header if no language is found in the cookie
    if (!lng) {
        const acceptLanguageHeader = req.headers.get('Accept-Language');
        if (acceptLanguageHeader) {
            lng = acceptLanguage.get(acceptLanguageHeader);
        }
    }

    // Default to the fallback language if no language was determined
    if (!lng) {
        lng = fallbackLng;
    }


    // Adjust the pathname to remove the basePath for language checking
    const pathname = req.nextUrl.pathname.replace(basePath, '');

    // Check if the pathname starts with a supported language and set it as preferred language
    const pathLang = languages.find((loc) => pathname.startsWith(`/${loc}`));

    const response = NextResponse.next();


    if (pathLang) {
        // If the path starts with a supported language, set it in the cookies with an expiry of 1 month

        // Calculate the expiration date (1 month from now)
        const expires = new Date();
        expires.setMonth(expires.getMonth() + 1);

        // Manually set the Set-Cookie header
        response.headers.set('Set-Cookie', `${cookieName}=${pathLang}; Expires=${expires.toUTCString()}; path=/; domain=${domain}; SameSite=Lax`);

        return response;
    }

    // Redirect if the language in the path is not supported
    if (
        !languages.some((loc) => pathname.startsWith(`/${loc}`)) &&
        !pathname.startsWith(`/_next`)
    ) {
        // Redirect to the language-specific path if the language is not supported
        const redirectUrl = new URL(`${basePath}/${lng}${pathname}`, req.url);
        redirectUrl.search = req.nextUrl.search; // Preserve query parameters
        return NextResponse.redirect(redirectUrl);
    }

    // If there is a referer header, set a cookie with the language found in the referer path
    if (req.headers.has('referer')) {
        const referer = req.headers.get('referer');
        if (referer) {
            const refererUrl = new URL(referer);
            const lngInReferer = languages.find((l) => refererUrl.pathname.startsWith(`/${l}`));
            if (lngInReferer) {
                // Set the cookie with an expiration date of 1 month
                const expires = new Date();
                expires.setMonth(expires.getMonth() + 1);
                response.headers.set('Set-Cookie', `${cookieName}=${lngInReferer}; Expires=${expires.toUTCString()}; path=/; domain=${domain}; SameSite=Lax`);
            }
        }
    }

    return response;
}