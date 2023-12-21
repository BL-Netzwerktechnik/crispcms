<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace crisp\api;

use crisp\api\lists\Languages;
use crisp\core;
use crisp\core\Crypto;
use crisp\core\Logger;
use crisp\core\Themes;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Maltyxx\ImagesGenerator\ImagesGeneratorProvider;

/**
 * Some useful helper functions.
 */
class Helper
{
    /**
     * Create a directory with the correct permissions.
     *
     * @param  string $dir
     * @return bool
     */
    public static function createDir(string $dir): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return mkdir($dir) && chown($dir, 33) && chgrp($dir, 33);
    }

    public static function prettyFormatNumber(int $num): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($num > 1000) {

            $x = round($num);
            $x_number_format = number_format($x);
            $x_array = explode(',', $x_number_format);
            $x_parts = ['k', 'm', 'b', 't'];
            $x_count_parts = count($x_array) - 1;
            $x_display = $x;
            $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
            $x_display .= $x_parts[$x_count_parts - 1];

            return $x_display;
        }

        return $num;
    }

    /**
     * Generate the S3 URL.
     *
     * @param  string      $bucket
     * @param  string      $region
     * @param  string|null $template
     * @return string
     */
    public static function getS3Url(string $bucket, string $region, string $template = null): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($template === null) {
            $template = "https://{{bucket}}.s3.{{region}}.amazonaws.com";
        }

        return strtr(
            $template,
            [
                "{{bucket}}" => $bucket,
                "{{region}}" => $region,
            ]
        );
    }

    /**
     * Generate an up to date mime array.
     *
     * @see https://www.php.net/manual/en/function.mime-content-type.php
     *
     * @param  string $url
     * @return array
     */
    public static function generateUpToDateMimeArray(string $url = "https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types"): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (!Cache::isExpired("mime_types")) {
            return json_decode(Cache::get("mime_types"), true);
        }

        $mimetypes = [];
        foreach (explode("\n", file_get_contents($url)) as $line) {
            $line = trim($line);
            if (str_starts_with($line, "#") || $line == "") {
                continue;
            }
            $explodedLine = preg_split('/\s+/', $line);
            list($mimetype, $extension) = $explodedLine;
            $mimetypes[$extension] = $mimetype;
        }

        Cache::write("mime_types", json_encode($mimetypes), time() + 3600);

        return $mimetypes;
    }

    /**
     * Detect the mimetype of a file.
     *
     * @param  string      $file
     * @return string|null
     */
    public static function detectMimetype(string $file): string|null
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $mappings = self::generateUpToDateMimeArray();

        $splitExt = explode(".", $file);
        $extension = end($splitExt);

        return $mappings[$extension];
    }

    /**
     * Get a list of all files in a directory recursively.
     *
     * @see https://stackoverflow.com/questions/24783862/list-all-the-files-and-folders-in-a-directory-with-php-recursive-function
     *
     * @param        $dir
     * @param        $results
     * @return array
     */
    public static function getDirRecursive($dir, &$results = []): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } elseif ($value != "." && $value != "..") {
                self::getDirRecursive($path, $results);
                if (!is_dir($path)) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    /**
     * Generate a nginx like access log.
     *
     * @return string
     */
    public static function getRequestLog(): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return sprintf(
            '%s - [%s] "%s %s %s" %s "%s"',
            self::getRealIpAddr(),
            date("d/M/Y:H:i:s O"),
            $_SERVER["REQUEST_METHOD"],
            $_SERVER["REQUEST_URI"],
            $_SERVER['SERVER_PROTOCOL'],
            http_response_code(),
            $_SERVER["HTTP_USER_AGENT"]
        );
    }

    /**
     * Get the Crisp Instance ID.
     *
     * @return string
     */
    public static function getInstanceId(): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (file_exists(core::PERSISTENT_DATA . "/.instance_id")) {
            return file_get_contents(core::PERSISTENT_DATA . "/.instance_id");
        }
        $InstanceId = Crypto::UUIDv4("I");

        file_put_contents(core::PERSISTENT_DATA . "/.instance_id", $InstanceId);

        return $InstanceId;
    }

    /**
     * Check if the user is on a mobile device.
     *
     * @return bool TRUE if the user is on mobile
     */
    public static function isMobile($UserAgent = null): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $UserAgent = ($UserAgent ?? $_SERVER['HTTP_USER_AGENT']);

        return preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $UserAgent);
    }

    /**
     * Gets the real ip address even behind a proxy.
     *
     * @return string containing the IP of the user
     */
    public static function getRealIpAddr(): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   // to check ip is pass from proxy
            return trim(explode(",", $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {   // to check ip is pass from proxy
            return trim(explode(",", $_SERVER['HTTP_X_REAL_IP'])[0]);
        }

        return $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
    }

    /**
     * Get the current locale a user has set.
     *
     * @return string current letter code
     */
    public static function getLocale(): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $Locale = $_GET["crisp_locale"] ?? locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        if (!array_key_exists($Locale, array_column(Languages::fetchLanguages(false), null, 'code'))) {
            $Locale = $_ENV['DEFAULT_LOCALE'] ?? 'en';
        }

        if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . 'language']) && !isset($_GET["crisp_locale"])) {
            $Locale = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . 'language'];
        }

        return $Locale;
    }

    /**
     * Sets the locale and saves in a cookie.
     *
     * @return bool
     */
    public static function setLocale(): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return setcookie(\crisp\core\Config::$Cookie_Prefix . 'language', self::getLocale(), time() + (86400 * 30), '/');
    }

    /**
     * Filter a string and remove non-alphanumeric and spaces.
     *
     * @param  string $String The string to filter
     * @return string Filtered string
     */
    public static function filterAlphaNum(string $String): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return str_replace(' ', '-', strtolower(preg_replace('/[^0-9a-zA-Z\-_]/', '-', $String)));
    }

    /**
     * Generate a placeholder image.
     *
     * @param string $Text The text to display
     * @param string $Size The in pixels to create the image with
     */
    public static function PlaceHolder(string $Size = '150x150', string|true $Text = true, $backgroundColor = null, $textColor = null)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        list($width, $height) = explode("x", $Size);
        $faker = \Faker\Factory::create();
        $faker->addProvider(new ImagesGeneratorProvider($faker));

        $imgPath = $faker->imageGenerator(width: $width, height: $height, text: $Text, backgroundColor: $backgroundColor, textColor: $textColor);
        $imgData = file_get_contents($imgPath);
        unlink($imgPath);

        return $imgData;
    }

    /**
     * Generate a placeholder image.
     *
     * @param string $Text The text to display
     * @param string $Size The in pixels to create the image with
     */
    public static function Faker(): FakerGenerator
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $faker = Factory::create();

        return $faker;
    }

    /**
     * Get the current commit hash.
     *
     * @return string|null
     */
    public static function getCommitHash(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return $_ENV['GIT_COMMIT'] ?: trim(exec('git describe --tags --always'));
    }

    /**
     * Generate a link with locale.
     *
     * @param  string $Path
     * @param  false  $External
     * @return string
     */
    public static function generateLink(string $Path, bool $External = false, string|false|null $Locale = false, ?string $UtmID = null, ?string $UtmSource = null, ?string $UtmMedium = null, ?string $UtmCampaign = null, ?string $UtmTerm = null, ?string $UtmContent = null): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        parse_str(parse_url($Path, PHP_URL_QUERY), $parameters);

        $CleanedPath = strtok($Path, '?');

        if (!empty($UtmID)) {
            $parameters["utm_id"] = $UtmID;
        }
        if (!empty($UtmSource)) {
            $parameters["utm_source"] = $UtmSource;
        }
        if (!empty($UtmMedium)) {
            $parameters["utm_medium"] = $UtmMedium;
        }
        if (!empty($UtmCampaign)) {
            $parameters["utm_campaign"] = $UtmCampaign;
        }
        if (!empty($UtmTerm)) {
            $parameters["utm_term"] = $UtmTerm;
        }
        if (!empty($UtmContent)) {
            $parameters["utm_content"] = $UtmContent;
        }
        if ($Locale !== false) {
            $parameters["crisp_locale"] = $Locale;
        }

        if (empty($parameters)) {
            $Destination = $Path;
        } else {
            $Destination = sprintf("%s?%s", $CleanedPath, http_build_query($parameters));
        }

        if ($External || (str_starts_with($Destination, "http://") || str_starts_with($Destination, "https://"))) {
            $urlConstruct = sprintf("%s", $Destination);
        } else {
            $urlConstruct = sprintf("/%s", $Destination);
        }

        return $urlConstruct;
    }

    /**
     * Just a pretty print for var_dump.
     *
     * @param string pretty var_dump
     */
    public static function prettyDump($var): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        echo sprintf('<pre>%s</pre>', var_export($var, true));
    }

    /**
     * Check if a Template exists within a specific theme.
     *
     * @param  string $Theme    The theme to search with
     * @param  string $Template The Template name
     * @return bool
     */
    public static function templateExists(string $Template): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return file_exists(Themes::getThemeDirectory() . "/templates/$Template");
    }

    /**
     * Truncates a text and appends "..." to the end.
     *
     * @param         $text
     * @param  int    $length
     * @param  string $ending
     * @param  bool   $exact
     * @param  bool   $considerHtml
     * @return string
     */
    public static function truncateText($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = [];
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

    /**
     * Check if a string is serialized.
     *
     * @see https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/functions.php#L611
     *
     * @param  string $data   The Data to check
     * @param  bool   $strict Strict Checking
     * @return bool
     */
    public static function isSerialized(string $data, bool $strict = true): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        // if it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== $data[strlen($data) - 2]) {
                        return false;
                    }
                } elseif (!str_contains($data, '"')) {
                    return false;
                }
                // or else fall through
                // no break
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';

                return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }

        return false;
    }

    /**
     * @return string
     */
    public static function currentURL(): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    public static function in_array_any($needles, $haystack): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return !empty(array_intersect($needles, $haystack));
    }
}
