<?php

namespace mindplay;

use ReflectionFunction;

/**
 * This class acts as a pseudo-namespace for translation functions
 *
 * Language codes are two-letter ISO-639-1 language codes, such as "en", "da", "es", etc.
 *
 * Translation domain names take the form "{vendor}/{package}", where the package name
 * may contain several names separated by slashes.
 *
 * @link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
 */
abstract class lang
{
    /**
     * @var string default language code
     */
    const DEFAULT_LANGUAGE = "en";

    /**
     * Use this property to set an error callback - you can use this for error reporting in test-suites.
     *
     * @var callable function ($message) : void
     */
    public static $on_error;

    /**
     * @var string active language code
     */
    protected static $code;

    /**
     * @var (string|callable)[][] map where "{domain}/{code}" => translation strings or callables
     */
    protected static $lang = [];

    /**
     * @var string[] map where "{domain}" or "{domain}/{code}" => absolute path to language file
     */
    protected static $paths = [];

    /**
     * Change the current active language code.
     *
     * @param string $code two-letter ISO-639-1 language code (such as "en", "da", "es", etc.)
     *
     * @link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    public static function set($code)
    {
        self::$code = $code;
    }

    /**
     * Register the physical base path of language files for a given translation domain name.
     *
     * @param string $domain translation domain name
     * @param string $path   absolute path to language directory (or base filename, without ".php")
     */
    public static function register($domain, $path)
    {
        self::$paths[$domain] = $path;
    }

    /**
     * Translate the given text in the given domain and substitute the given tokens.
     *
     * @param string     $domain translation domain name
     * @param string     $text   english text
     * @param array|null $tokens map where token name => replacement string
     *
     * @return string
     */
    public static function text($domain, $text, array $tokens = null)
    {
        return self::translate(self::$code, $domain, $text, $tokens);
    }

    /**
     * Obtain a translation callback for a given domain, optionally for a specific language.
     *
     * The returned function has the following signature:
     *
     *     function (string $text, array $tokens) : string
     *
     * This may be useful in a view/template, for example, so you don't have to repeat the
     * domain name for every call. It may also be useful to inject this function as a dependency.
     *
     * @param string      $domain translation domain name
     * @param string|null $code   optional language code (defaults to the current language)
     *
     * @return callable function ($text, array $tokens) : string
     */
    public static function domain($domain, $code = null)
    {
        return function ($text, array $tokens = []) use ($domain, $code) {
            return self::translate($code ?: self::$code, $domain, $text, $tokens);
        };
    }

    /**
     * This is the lowest-level function, which requires every parameter to be given explicitly.
     *
     * @param string $code   two-letter ISO-639-1 language code
     * @param string $domain translation domain name
     * @param string $text   english text
     * @param array  $tokens map where token name => replacement string
     *
     * @return string
     */
    public static function translate($code, $domain, $text, array $tokens = null)
    {
        $name = "{$domain}/{$code}";

        if (! isset(self::$lang[$name])) {
            self::load($name);
        }

        $has_template = isset(self::$lang[$name][$text]);

        if (self::$on_error && !$has_template) {
            call_user_func(self::$on_error, "missing translation of '{$text}' for: {$name}");
        }

        $template = $has_template
            ? self::$lang[$name][$text]
            : $text;

        if (is_callable($template)) {
            // perform translation with a user-defined function:

            $args = [];

            if ($tokens) {
                $func = new ReflectionFunction($template);

                foreach ($func->getParameters() as $param) {
                    $args[] = isset($tokens[$param->name])
                        ? $tokens[$param->name]
                        : "{{$param->name}}"; // ignore missing tokens
                }
            }

            return call_user_func_array($template, $args);
        } else {
            if ($tokens) {
                // perform translation using simple string substitution:

                return strtr(
                    $template,
                    array_combine(
                        array_map(
                            function ($key) {
                                return "{{$key}}";
                            },
                            array_keys($tokens)
                        ),
                        $tokens
                    )
                );
            } else {
                // no token substitution required:

                return $template;
            }
        }
    }

    /**
     * Reset the internal state of the language registry.
     *
     * This may be useful for unit-testing or other special situations.
     */
    public static function reset()
    {
        self::$code = lang::DEFAULT_LANGUAGE;
        self::$lang = [];
        self::$paths = [];
    }

    /**
     * Internally find a load a given language file.
     *
     * @param string $name full language file base name, e.g. "{domain}/{code}"
     */
    protected static function load($name)
    {
        $domain_names = explode('/', $name);

        while (count($domain_names)) {
            $parent_domain = implode('/', $domain_names);

            if (isset(self::$paths[$parent_domain])) {
                $path = self::$paths[$parent_domain] . substr($name, strlen($parent_domain)) . '.php';

                if (file_exists($path)) {
                    self::$lang[$name] = require $path;

                    break;
                }
            }

            array_pop($domain_names);
        }

        if (! isset(self::$lang[$name])) {
            self::$lang[$name] = []; // no translation file available

            if (self::$on_error) {
                call_user_func(self::$on_error, "no translation file found for: {$name}");
            }
        }
    }
}
