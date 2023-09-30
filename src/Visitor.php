<?php

namespace Scaliter;

use Scaliter\Request;

class Visitor
{
    public static $IP, $platform, $browser, $languages, $language, $timezone, $timeformat, $locale;

    public static function getBrowser($agent)
    {
        $browser = match (1) {
            preg_match('/MSIE/i',       $agent) && !preg_match('/Opera/i', $agent) => ['Internet Explorer', 'MSIE'],
            preg_match('/Firefox/i',    $agent) => ['Mozilla Firefox', 'Firefox'],
            preg_match('/Chrome/i',     $agent) => ['Google Chrome', 'Chrome'],
            preg_match('/Safari/i',     $agent) => ['Apple Safari', 'Safari'],
            preg_match('/Opera/i',      $agent) => ['Opera', 'Opera'],
            preg_match('/Netscape/i',   $agent) => ['Netscape', 'Netscape'],
            default => ['unknown', 'unknown']
        };
        $bname = $browser[0];
        $ubb = $browser[1];

        $known = array('Version', $ubb, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        preg_match_all($pattern, $agent, $matches);

        $version = count($matches['browser']) != 1 ? (strripos($agent, "Version") < strripos($agent, $ubb) ? $matches['version'][0] : ($matches['version'][1] ?? '?')) : ($matches['version'][0] ?? '?');

        return $bname . " " . $version;
    }

    public static function getPlatform($agent)
    {
        if (preg_match('/linux/i', $agent)) {
            return 'LINUX';
        } elseif (preg_match('/macintosh|mac os x/i', $agent)) {
            return 'MAC';
        } elseif (preg_match('/windows|win32/i', $agent)) {
            return 'WINDOWS';
        }
        return 'unknown';
    }

    public static function initialize()
    {
        $agent = Request::server('HTTP_USER_AGENT')->value;

        self::$IP           = self::getIp();
        self::$platform     = self::getPlatform($agent);
        self::$browser      = self::getBrowser($agent);

        $languages          = self::getLanguages();
        self::$language     = $languages[0] ?? '';
        self::$languages    = implode(';', $languages);

        self::$locale = Request::server('HTTP_CONTENT_LOCALE')->value;

        $timeformat = Request::server('HTTP_ACCEPT_CLOCK')->value;
        if (!$timeformat) {
            $timeformat = Request::cookie('timeformat')->value == 'h12' ? 'h12' : 'h23';
        } else {
            $timeformat = $timeformat == 'h12' ? 'h12' : 'h23';
        }
        self::$timeformat   = $timeformat;

        $timezone = Request::server('HTTP_ACCEPT_TIMEZONE')->value;
        if (!$timezone) {
            $timezone = Request::cookie('timezone')->value;
        }
        self::$timezone     = $timezone;
    }
    public static function getIp()
    {
        $cloudFlare = Request::server('HTTP_CF_CONNECTING_IP')->value;

        $client     = $cloudFlare ? $cloudFlare : Request::server('HTTP_CLIENT_IP')->value;
        $ipAddress  = $cloudFlare ? $cloudFlare : Request::server('REMOTE_ADDR')->value;
        $forward    = Request::server('HTTP_X_FORWARDED_FOR')->value;

        if (filter_var($client, FILTER_VALIDATE_IP))
            return $client;
        elseif (filter_var($forward, FILTER_VALIDATE_IP))
            return $forward;
        return $ipAddress;
    }

    public static function getLanguages()
    {
        $userLanguages = [];
        $validLanguages = ["aa", "ab", "ae", "af", "ak", "am", "an", "ar", "as", "av", "ay", "az", "ba", "be", "bg", "bh", "bi", "bm", "bn", "bo", "br", "bs", "ca", "ce", "ch", "co", "cr", "cs", "cu", "cv", "cy", "da", "de", "dv", "dz", "ee", "el", "en", "eo", "es", "et", "eu", "fa", "ff", "fi", "fj", "fo", "fr", "fy", "ga", "gd", "gl", "gn", "gu", "gv", "ha", "he", "hi", "ho", "hr", "ht", "hu", "hy", "hz", "ia", "id", "ie", "ig", "ii", "ik", "io", "is", "it", "iu", "ja", "jv", "ka", "kg", "ki", "kj", "kk", "kl", "km", "kn", "ko", "kr", "ks", "ku", "kv", "kw", "ky", "la", "lb", "lg", "li", "ln", "lo", "lt", "lu", "lv", "mg", "mh", "mi", "mk", "ml", "mn", "mr", "ms", "mt", "my", "na", "nb", "nd", "ne", "ng", "nl", "nn", "no", "nr", "nv", "ny", "oc", "oj", "om", "or", "os", "pa", "pi", "pl", "ps", "pt", "qu", "rm", "rn", "ro", "ru", "rw", "sa", "sc", "sd", "se", "sg", "si", "sk", "sl", "sm", "sn", "so", "sq", "sr", "ss", "st", "su", "sv", "sw", "ta", "te", "tg", "th", "ti", "tk", "tl", "tn", "to", "tr", "ts", "tt", "tw", "ty", "ug", "uk", "ur", "uz", "ve", "vi", "vo", "wa", "wo", "xh", "yi", "yo", "za", "zh", "zu"];
        $language = explode(';', Request::server("HTTP_ACCEPT_LANGUAGE")->value);
        foreach ($language as $lan) {
            $lang = explode(",", $lan);
            $lang = end($lang);
            if (in_array($lang, $validLanguages))
                $userLanguages[] = $lang;
        }
        return $userLanguages;
    }
    public static function formatTime($time): string
    {
        return self::$timeformat == 'h23' ? date('d.m.Y. H:i', strtotime($time)) : date('d.m.Y. h:ia', strtotime($time));
    }
    public static function getCountryByIP(string $ip_address)
    {
        $request = file_get_contents("http://ip-api.com/json/$ip_address");
        return json_decode($request, true);
    }
}
