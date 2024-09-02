<?php

namespace App\modules\bookingfrontend\helpers;

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Translation;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Sanitizer;

class LangHelper
{
    public function process(Request $request, Response $response, array $args = []): Response
    {
        $userSettings = Settings::getInstance()->get('user');
        $selected_lang = Sanitizer::get_var('selected_lang', 'string', 'COOKIE');

        // Check for lang in route parameter
        if (isset($args['lang'])) {
            $selected_lang = $args['lang'];
        }
        // If not in route parameter, check GET parameter
        elseif ($request->getQueryParams()['lang'] ?? false) {
            $selected_lang = $request->getQueryParams()['lang'];
        }

        if ($selected_lang) {
            $sessions = Sessions::getInstance();
            $sessions->phpgw_setcookie('selected_lang', $selected_lang, (time() + (60 * 60 * 24 * 14)));
        }

        $userlang = $selected_lang ?: $userSettings['preferences']['common']['lang'];

        $return_data = Cache::system_get('phpgwapi', "lang_{$userlang}", true);

        if (!$return_data) {
            $translation = Translation::getInstance();
            $translation->set_userlang($userlang, true);
            $translation->populate_cache();
            $return_data = Cache::system_get('phpgwapi', "lang_{$userlang}", true);
        }

        $response->getBody()->write(json_encode($return_data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}