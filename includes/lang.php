<?php
/**
 * Language module.
 *
 * The user can switch between English (en) and Kiswahili (sw) using the
 * toggle in the navigation bar. The choice is stored in the session.
 */

/**
 * Set the active language (called by the toggle link ?lang=sw).
 */
function set_language($lang)
{
    $lang = in_array($lang, ['en', 'sw'], true) ? $lang : 'en';
    $_SESSION['lang'] = $lang;
}

/**
 * Get the active language code.
 */
function current_lang()
{
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Load the strings for the active language (cached per request).
 */
function lang_strings()
{
    static $strings = null;
    static $loadedLang = null;

    $lang = current_lang();
    if ($strings === null || $loadedLang !== $lang) {
        $file = __DIR__ . '/../language/' . $lang . '.php';
        $strings = file_exists($file) ? require $file : require __DIR__ . '/../language/en.php';
        $loadedLang = $lang;
    }
    return $strings;
}

/**
 * Translate a key. Falls back to the key itself if not found.
 */
function t($key)
{
    $strings = lang_strings();
    return $strings[$key] ?? $key;
}
