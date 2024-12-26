<?php

use App\Lib\Captcha;
use App\Notify\Notify;
use App\Lib\ClientInfo;
use App\Models\SiteSetting;
use App\Models\Translation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

if (!function_exists('setting')) {

    function setting($key = null)
    {
        // Retrieve cached settings or fetch from database if not cached
        $settings = Cache::rememberForever('SITE_SETTINGS', function () {
            return SiteSetting::pluck('st_value', 'st_key')->toArray();
        });

        // If a specific key is requested
        if ($key) {
            if (array_key_exists($key, $settings)) {
                return $settings[$key];
            }

            // Add a new setting with default value if the key does not exist
            $newSetting = SiteSetting::create([
                'st_key' => $key,
                'value' => 'NotSet',
            ]);

            // Update the cached settings
            $settings[$key] = $newSetting->value;
            Cache::forever('SITE_SETTINGS', $settings);

            return 'NotSet';
        }

        // Return all settings if no specific key is requested
        return $settings;
    }
}


// transaltion
if (!function_exists('translate')) {
    function translate($key, $lang = null, $addslashes = false)
    {
        // Determine the language to use
        if (is_null($lang)) {
            $lang = Session::get('locale', App::getLocale());
        }

        // Generate a sanitized language key
        $lang_key = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', strtolower($key)));

        // Fetch English translations from cache or database
        $translations_en = Cache::rememberForever('translations-en', function () {
            return Translation::where('lang', 'en')->pluck('lang_value', 'lang_key')->toArray();
        });

        // Add missing English translation if not present
        if (!isset($translations_en[$lang_key])) {
            Translation::updateOrCreate(
                ['lang' => 'en', 'lang_key' => $lang_key],
                ['lang_value' => str_replace(["\r", "\n", "\r\n"], "", $key)]
            );
            Cache::forget('translations-en');
        }

        // Fetch translations for the specified language
        $translation_locale = Cache::rememberForever("translations-{$lang}", function () use ($lang) {
            return Translation::where('lang', $lang)->pluck('lang_value', 'lang_key')->toArray();
        });

        // Return translation if available in the specified language
        if (isset($translation_locale[$lang_key])) {
            $translation = trim($translation_locale[$lang_key]);
            return $addslashes ? addslashes($translation) : $translation;
        }

        // Fallback to default language if translation not found
        $default_lang = env('DEFAULT_LANGUAGE', 'en');
        $translations_default = Cache::rememberForever("translations-{$default_lang}", function () use ($default_lang) {
            return Translation::where('lang', $default_lang)->pluck('lang_value', 'lang_key')->toArray();
        });

        if (isset($translations_default[$lang_key])) {
            $translation = trim($translations_default[$lang_key]);
            return $addslashes ? addslashes($translation) : $translation;
        }

        // Fallback to English translation
        if (isset($translations_en[$lang_key])) {
            $translation = trim($translations_en[$lang_key]);
            return $addslashes ? addslashes($translation) : $translation;
        }

        // Return the original key as the last resort
        return trim($key);
    }
}

// Captcha  
if (!function_exists('verificationCode')) {

    function verificationCode($length)
    {
        if ($length == 0) return 0;
        $min = pow(10, $length - 1);
        $max = (int) ($min - 1) . '9';
        return random_int($min, $max);
    }
}

if (!function_exists('verifyCaptcha')) {
    function verifyCaptcha()
    {
        return Captcha::verify();
    }
}

// IP and browser

if (!function_exists('getRealIP')) {
    function getRealIP()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        //Deep detect ip
        if (filter_var(@$_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        }
        if (filter_var(@$_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (filter_var(@$_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if ($ip == '::1') {
            $ip = '127.0.0.1';
        }

        return $ip;
    }
}

if (!function_exists('getIpInfo')) {
    function getIpInfo()
    {
        $ipInfo = ClientInfo::ipInfo();
        return $ipInfo;
    }
}

if (!function_exists('osBrowser')) {
    function osBrowser()
    {
        $osBrowser = ClientInfo::osBrowser();
        return $osBrowser;
    }
}


// Mail and notiifcations
function notify($user, $templateName, $shortCodes = null, $sendVia = null, $createLog = true, $pushImage = null)
{
    $globalShortCodes = [
        'site_name' => SETTING['site_name'],
        'site_currency' => SETTING['cur_text'],
        'currency_symbol' => SETTING['cur_sym'],
    ];

    if (gettype($user) == 'array') {
        $user = (object) $user;
    }

    $shortCodes = array_merge($shortCodes ?? [], $globalShortCodes);

    $notify = new Notify($sendVia);
    $notify->templateName = $templateName;
    $notify->shortCodes = $shortCodes;
    $notify->user = $user;
    $notify->createLog = $createLog;
    $notify->pushImage = $pushImage;
    $notify->userColumn = isset($user->id) ? $user->getForeignKey() : 'user_id';
    $notify->send();
}
