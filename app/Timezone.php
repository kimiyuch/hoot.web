<?php

namespace App;

use Illuminate\Http\Request;

/**
 * The visitor's timezone. The browser sends it in the "tz" cookie (set by resources/js/app.js);
 * until it has, the one in config/hoot.php is used.
 */
class Timezone
{
    public const COOKIE = 'tz';

    public static function for(Request $request): string
    {
        $timezone = $request->cookie(self::COOKIE);

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('hoot.timezone');
    }
}
