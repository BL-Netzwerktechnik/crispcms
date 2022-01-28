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
use crisp\core\Postgres;
use PDO;
use stdClass;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;

/**
 * Some useful helper functions
 */
class Helper
{

    public static function Log(int $type, string $message): void
    {

        if(empty($_ENV["VERBOSITY"])) $_ENV["VERBOSITY"] = 2;

        $typeHuman = match($type){
            1 => "ERROR",
            2 => "INFO",
            3 => "DEBUG"
        };

        if($_ENV["VERBOSITY"] < 3 && $type === 3) return;
        if($_ENV["VERBOSITY"] < 2 && $type === 2) return;
        if($_ENV["VERBOSITY"] < 1 && $type === 1) return;


        if(php_sapi_name() == "cli"){
            echo "[$typeHuman] $message". PHP_EOL;
        } else {
            error_log("[$typeHuman] $message". PHP_EOL);
        }
    }

    public static function getMachineID(): string
    {
        $result = null;

        if (PHP_OS_FAMILY === 'Windows') {

            $output = shell_exec("diskpart /s select disk 0\ndetail disk");
            $lines = explode("\n", $output);
            $result = array_filter($lines, static function ($line) {
                return stripos($line, "ID:") !== false;
            });
            if (count($result) > 0) {
                $array = array_values($result);
                $result = array_shift($array);
                $result = explode(":", $result);
                $result = trim(end($result));
            } else {
                $result = $output;
            }
        } else if (file_exists('/etc/machine-id')) {
            $result = file_get_contents('/etc/machine-id');
        } else if (file_exists('/var/lib/dbus/machine-id')) {
            $result = file_get_contents('/var/lib/dbus/machine-id');
        }


        Helper::Log(3, "MachineID: " . hash('md5', trim($result)));

        return hash('md5', trim($result));
    }

    /**
     * Check if the user is on a mobile device
     * @return boolean TRUE if the user is on mobile
     */
    public static function isMobile($UserAgent = null): bool
    {
        $UserAgent = ($UserAgent ?? $_SERVER['HTTP_USER_AGENT']);
        return preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $UserAgent);
    }

    /**
     * @param $BitmaskFlag
     * @param null $apikey
     * @return bool|int
     */
    public static function hasApiPermissions($BitmaskFlag, $apikey = null): bool|int
    {

        if ($apikey === null) {
            $apikey = self::getAPIKeyFromHeaders();

            if ($apikey === null) {
                return false;
            }
        }

        $keyDetails = self::getAPIKeyDetails($apikey);

        if (!$keyDetails) {
            return false;
        }


        return ($keyDetails['permissions'] & $BitmaskFlag);
    }

    /**
     * @param string $ApiKey
     * @return mixed
     */
    public static function getAPIKeyDetails(string $ApiKey): mixed
    {


        $Postgres = new Postgres();

        $statement = $Postgres->getDBConnector()->prepare('SELECT * FROM apikeys WHERE key = :key');

        $statement->execute([':key' => $ApiKey]);

        if ($statement->rowCount() > 0) {
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * @param string|null $apikey
     * @return bool
     */
    public static function getAPIKey(string $apikey = null): bool
    {

        $Postgres = new Postgres();

        $statement = $Postgres->getDBConnector()->prepare('SELECT * FROM apikeys WHERE key = :key AND revoked = 0 AND (expires_at is null OR expires_at > NOW())');

        if ($apikey === null) {
            $apikey = self::getAPIKeyFromHeaders();

            if ($apikey === null) {
                return false;
            }
        }

        $statement->execute([':key' => $apikey]);

        return $statement->rowCount() > 0;
    }

    public static function getAPIKeyFromHeaders(): ?string
    {
        if (isset(apache_request_headers()['Authorization'])) {
            return apache_request_headers()['Authorization'];
        }
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }
        if (isset($_SERVER['HTTP_X_KEY'])) {
            return $_SERVER['HTTP_X_KEY'];
        }
        if (isset($_SERVER['HTTP_X_API'])) {
            return $_SERVER['HTTP_X_API'];
        }
        if (isset($_SERVER['HTTP_API'])) {
            return $_SERVER['HTTP_API'];
        }
        if (isset($_SERVER['HTTP_KEY'])) {
            return $_SERVER['HTTP_KEY'];
        }
        return null;
    }

    /**
     * Gets the real ip address even behind a proxy
     * @return String containing the IP of the user
     */
    public static function getRealIpAddr(): string
    {
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return "0.0.0.0";
    }

    /**
     * Get the current locale a user has set
     * @return string current letter code
     */
    public static function getLocale(): string
    {
        $Locale = $GLOBALS['route']->Language ?? locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        if (!array_key_exists($Locale, array_column(Languages::fetchLanguages(false), null, 'code'))) {
            $Locale = $_ENV['DEFAULT_LOCALE'] ?? 'en';
        }

        if (isset($_COOKIE[\crisp\core\Config::$Cookie_Prefix . 'language']) && !isset($GLOBALS['route']->Language)) {
            $Locale = $_COOKIE[\crisp\core\Config::$Cookie_Prefix . 'language'];
        }
        return $Locale;
    }

    /**
     * Sets the locale and saves in a cookie
     *
     * @return bool
     */
    public static function setLocale(): bool
    {
        return setcookie(\crisp\core\Config::$Cookie_Prefix . 'language', self::getLocale(), time() + (86400 * 30), '/');
    }

    /**
     * Filter a string and remove non-alphanumeric and spaces
     * @param string $String The string to filter
     * @return string Filtered string
     */
    public static function filterAlphaNum(string $String): string
    {
        return str_replace(' ', '-', strtolower(preg_replace('/[^0-9a-zA-Z\-_]/', '-', $String)));
    }

    /**
     * Generate a placeholder image
     * @param string $Text The text to display
     * @param string $Size The in pixels to create the image with
     */
    public static function PlaceHolder(string $Size = '150x150', string $Text = null)
    {

        if ($Text === null) {
            $Text = $Size;
        }

        $fontSize = 5;
        $dimensions = explode('x', $Size);

        $w = $dimensions[0] ?? 100;
        $h = $dimensions[1] ?? 100;
        $text = $Text ?? $w . 'x' . $h;

        if ($w < 50) {
            $fontSize = 1;
        }

        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 204, 204, 204);

        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        $fontWidth = imagefontwidth($fontSize);
        $textWidth = $fontWidth * strlen($text);
        $textLeft = ceil(($w - $textWidth) / 2);

        $fontHeight = imagefontheight($fontSize);
        $textHeight = $fontHeight;
        $textTop = ceil(($h - $textHeight) / 2);

        imagestring($im, $fontSize, $textLeft, $textTop, $text, 0x969696);


        ob_start();


        imagejpeg($im);

        $img_data = ob_get_contents();
        imagedestroy($im);

        ob_end_clean();

        return $img_data;

    }

    public static function LoremIpsum($count = 1, $max = 20)
    {

        $out = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
            'sed do eiusmod tempor incididunt ut labore et dolore magna ' .
            'aliqua.';
        $rnd = explode(' ',
            'a ab ad accusamus adipisci alias aliquam amet animi aperiam ' .
            'architecto asperiores aspernatur assumenda at atque aut beatae ' .
            'blanditiis cillum commodi consequatur corporis corrupti culpa ' .
            'cum cupiditate debitis delectus deleniti deserunt dicta ' .
            'dignissimos distinctio dolor ducimus duis ea eaque earum eius ' .
            'eligendi enim eos error esse est eum eveniet ex excepteur ' .
            'exercitationem expedita explicabo facere facilis fugiat harum ' .
            'hic id illum impedit in incidunt ipsa iste itaque iure iusto ' .
            'laborum laudantium libero magnam maiores maxime minim minus ' .
            'modi molestiae mollitia nam natus necessitatibus nemo neque ' .
            'nesciunt nihil nisi nobis non nostrum nulla numquam occaecati ' .
            'odio officia omnis optio pariatur perferendis perspiciatis ' .
            'placeat porro possimus praesentium proident quae quia quibus ' .
            'quo ratione recusandae reiciendis rem repellat reprehenderit ' .
            'repudiandae rerum saepe sapiente sequi similique sint soluta ' .
            'suscipit tempora tenetur totam ut ullam unde vel veniam vero ' .
            'vitae voluptas');
        $max = $max <= 3 ? 4 : $max;
        for ($i = 0, $add = $count - (int)$std; $i < $add; $i++) {
            shuffle($rnd);
            $words = array_slice($rnd, 0, mt_rand(3, $max));
            $out .= (!$std && $i == 0 ? '' : ' ') . ucfirst(implode(' ', $words)) . '.';
        }
        return $out;
    }


    public static function getCommitHash(): ?string
    {
        return $_ENV['GIT_COMMIT'] ?: trim(exec('git log --pretty="%h" -n1 HEAD'));
    }


    /**
     * Validates if the plugin name
     * @param string $Name The name of the plugin
     * @return array|boolean Array of errors if found, otherwise true
     */
    public static function isValidPluginName(string $Name): bool|array
    {

        $Matches = [];

        if (preg_match_all('/[^0-9a-zA-Z\-_]/', $Name) > 0) {
            $Matches[] = 'STRING_CONTAINS_NON_ALPHA_NUM';
        }
        if (str_contains($Name, ' ')) {
            $Matches[] = 'STRING_CONTAINS_SPACES';
        }
        if (preg_match('/[A-Z]/', $Name)) {
            $Matches[] = 'STRING_CONTAINS_UPPERCASE';
        }

        return (count($Matches) > 0 ? $Matches : true);
    }

    /**
     * @param string $Path
     * @param false $External
     * @return string
     */
    public static function generateLink(string $Path, bool $External = false): string
    {
        return ($External ? $Path : '/' . self::getLocale() . "/$Path");
    }

    /**
     * @param $Route
     * @return stdClass
     */
    public static function processRoute($Route): stdClass
    {
        $_Route = explode('/', $Route);
        array_shift($_Route);
        if (isset($_SERVER['IS_API_ENDPOINT'])) {
            array_unshift($_Route, 'api');
        }

        $obj = new stdClass();
        $obj->Language = (lists\Languages::languageExists($_Route[0]) && $_Route[0] !== '' ? $_Route[0] : self::getLocale());
        $obj->Page = explode('?', ($_Route[1] === '' ? (strlen($_Route[0]) > 0 ? $_Route[0] : false) : $_Route[1]))[0];
        $obj->GET = array();
        if ($_Route[2] !== '') {
            $_RouteArray = $_Route;
            array_shift($_RouteArray);
            array_shift($_RouteArray);
            for ($i = 0, $iMax = count($_RouteArray); $i <= $iMax; $i += 2) {
                $key = $_RouteArray[$i];
                $value = $_RouteArray[$i + 1];
                if ($key !== '') {
                    if ($value === null) {
                        $obj->GET['q'] = explode('?', $key)[0];
                    } else {
                        $obj->GET[$key] = explode('?', $value)[0];
                    }
                }
            }
        }
        if (str_contains($Route, '?')) {
            $qexplode = explode('?', $Route);
            array_shift($qexplode);
            foreach ($qexplode as $key) {
                $key = explode('=', $key);
                $_GET[$key[0]] = $key[1];
            }
        }


        self::Log(3, "Processed route: " . var_export($obj, true));

        return $obj;
    }

    /**
     * Just a pretty print for var_dump
     * @param string pretty var_dump
     */
    public static function prettyDump($var): void
    {
        echo sprintf('<pre>%s</pre>', var_export($var, true));
    }

    /**
     * Check if a Template exists within a specific theme
     * @param string $Theme The theme to search with
     * @param string $Template The Template name
     * @return boolean
     */
    public static function templateExists(string $Theme, string $Template): bool
    {
        return file_exists(__DIR__ . "/../../../../themes/$Theme/templates/$Template");
    }

    /**
     * Truncates a text and appends "..." to the end
     * @param string $String The text to truncate
     * @param int $Length After how many chars should we truncate the text?
     * @param bool $AppendDots Should we append dots to the end of the string?
     * @return string
     */
    public static function truncateText($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true): string
    {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
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
     * Check if a string is serialized
     * @see https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/functions.php#L611
     * @param string $data The Data to check
     * @param bool $strict Strict Checking
     * @return boolean
     */
    public static function isSerialized(string $data, bool $strict = true): bool
    {
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
            case 's' :
                if ($strict) {
                    if ('"' !== $data[strlen($data) - 2]) {
                        return false;
                    }
                } elseif (!str_contains($data, '"')) {
                    return false;
                }
            // or else fall through
            case 'a' :
            case 'O' :
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        return false;
    }


    /**
     * @return string
     */
    public static function currentURL(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }


    public static function in_array_any($needles, $haystack): bool
    {
        return !empty(array_intersect($needles, $haystack));
    }

    public static function FormatTime($timestamp): string
    {
        setlocale(LC_TIME, "de_DE.utf8");
        // Get time difference and setup arrays
        $difference = time() - $timestamp;
        $periods = array("Sekunde", "Minute", "Stunde", "Tag", "Woche", "Monat", "Jahr");
        $lengths = array("60", "60", "24", "7", "4.35", "12");

        // Past or present
        if ($difference >= 0) {
            $ending = "vor";
        } else {
            $difference = -$difference;
            $ending = "in";
        }

        // Figure out difference by looping while less than array length
        // and difference is larger than lengths.
        $arr_len = count($lengths);
        for ($j = 0; $j < $arr_len && $difference >= $lengths[$j]; $j++) {
            $difference /= $lengths[$j];
        }

        // Round up
        $difference = round($difference);

        // Make plural if needed
        if ($difference != 1) {
            $periods[$j] .= "n";
        }

        // Default format
        $text = "$ending $difference $periods[$j]";

        // over 24 hours
        if ($j > 2) {
            // future date over a day formate with year
            if ($ending === "im") {
                if ($j == 3 && $difference == 1) {
                    $text = "Morgen um " . strftime("%H:%M", $timestamp);
                } else {
                    $text = date("%e %B, %Y um %H:%M", $timestamp);
                }
                return $text;
            }

            if ($j == 3 && $difference == 1) // Yesterday
            {
                $text = "Gestern um " . strftime("%H:%M", $timestamp);
            } else if ($j == 3) // Less than a week display -- Monday at 5:28pm
            {
                $text = strftime("%A um %H:%M", $timestamp);
            } else if ($j < 6 && !($j == 5 && $difference == 12)) // Less than a year display -- June 25 at 5:23am
            {
                $text = strftime("%e %B um %H:%M", $timestamp);
            } else // if over a year or the same month one year ago -- June 30, 2010 at 5:34pm
            {
                $text = strftime("%e %B, %Y um %H:%M", $timestamp);
            }
        }

        return $text;
    }


}
