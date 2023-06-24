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

use Carbon\Carbon;
use crisp\api\lists\Languages;
use crisp\core\Logger;
use crisp\core\LogTypes;
use crisp\core\Postgres;
use crisp\core\Themes;
use PDO;
use splitbrain\phpcli\CLI;
use stdClass;

/**
 * Some useful helper functions
 */
class Helper
{

    public static function createDir(string $dir): bool
    {
        return mkdir($dir) && chown($dir, 33) && chgrp($dir, 33);
    }

    public static function getS3Url(string $bucket, string $region, string $template = null): string
    {

        if($template === null){
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


    public static function detectMimetype(string $file){

        $mime = mime_content_type($file);

        $mappings = [
            "css" => "text/css",
            "js" => "text/javascript",
            "json" => "application/json"
        ];

        $splitExt = explode(".", $file);
        $extension = end($splitExt);

        if(array_key_exists($extension, $mappings)){
            return $mappings[$extension];
        }

        return $mime;
    }

    /**
     * @link https://stackoverflow.com/questions/24783862/list-all-the-files-and-folders-in-a-directory-with-php-recursive-function
     * @param $dir
     * @param $results
     * @return array
     */
    public static function getDirRecursive($dir, &$results = array()): array {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                self::getDirRecursive($path, $results);
                if(!is_dir($path)) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    public static function getRequestLog(): string
    {
        return sprintf('%s - [%s] "%s %s %s" %s "%s"',
            self::getRealIpAddr(),
            date("d/M/Y:H:i:s O"),
            $_SERVER["REQUEST_METHOD"],
            $GLOBALS["route"]->Raw ?: "/",
            $_SERVER['SERVER_PROTOCOL'],
            http_response_code(),
            $_SERVER["HTTP_USER_AGENT"]
        );
    }

    public static function Log(LogTypes|int $type, $message): void
    {

        $cli = new Logger();
        if(!isset($_ENV["NO_COLORS"])) {
            $cli->colors->enable();
        }
        if(is_numeric($type)){
            $type = LogTypes::from($type);
        }

        $debugMsg = is_string($message) ? $message : var_export($message, true);

            switch($type){
                case LogTypes::DEBUG:
                    if((int)$_ENV["VERBOSITY"] > 1){
                        $cli->debug(sprintf("[%s] %s", date(DATE_RFC2822), $debugMsg));
                    }
                    break;
                case LogTypes::ERROR:
                    $cli->error($debugMsg);
                    break;
                case LogTypes::INFO:
                    $cli->info($debugMsg);
                    break;
                case LogTypes::SUCCESS:
                    $cli->success($debugMsg);
                    break;
                case LogTypes::WARNING:
                    $cli->warning($debugMsg);
            }
    }

    public static function getInstanceId(): string
    {

        if(file_exists(\crisp\core::PERSISTENT_DATA . "/.instance_id")){
            return file_get_contents(\crisp\core::PERSISTENT_DATA . "/.instance_id");
        }
        $InstanceId = \crisp\core\Crypto::UUIDv4("I");

        file_put_contents(\crisp\core::PERSISTENT_DATA . "/.instance_id", $InstanceId);

        return $InstanceId;
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

        if (!empty($_SERVER['REMOTE_ADDR'])) {
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
        self::Log(LogTypes::DEBUG, "Route Obj: " . var_export($_Route, true));

        $obj = new stdClass();


        if(strlen($_Route[0]) === 2){
            $obj->Page = explode("?", $_Route[1])[0];
            $obj->Language = (lists\Languages::languageExists($_Route[0]) && $_Route[0] !== '' ? $_Route[0] : self::getLocale());
            $obj->LanguageParameter = true;
        }else{
            $obj->Page = explode("?", $_Route[0])[0];
            $obj->Language = self::getLocale();
            $obj->LanguageParameter = false;
        }


        /** /mypage/my_parameter */

        if($obj->LanguageParameter){
            $LookupIndex = 2;
        }else{
            $LookupIndex = 1;
        }


        if ($_Route[$LookupIndex] !== '' && ((count($_Route) > 2 && !IS_API_ENDPOINT) || (IS_API_ENDPOINT))) {
            $_RouteArray = $_Route;
            if(!IS_API_ENDPOINT){
                for ($i = 0; $i < count($_Route) - 1; $i++) {
                    array_shift($_RouteArray);
                }

            }else{
                array_shift($_RouteArray);
            }
            for ($i = 0, $iMax = count($_RouteArray); $i <= $iMax; $i += 2) {
                $key = $_RouteArray[$i];
                $value = $_RouteArray[$i + 1];
                if ($key !== '') {
                    if ($value === null) {
                            $val = explode('?', $key)[0];
                            if(strlen($val) > 0) {
                                $_GET['q'] = $val;
                            }
                    } else {
                        $_GET[$key] = explode('?', $value)[0];
                    }
                }
            }
        }


        $obj->Raw = implode("/", $_Route);
        if (str_contains($Route, '?')) {
            $qexplode = explode('?', $Route);
            array_shift($qexplode);
            foreach ($qexplode as $key) {
                $key = explode('=', $key);
                $_GET[$key[0]] = $key[1] ?? "";
            }
        }

        unset($_GET["route"]);

        self::Log(LogTypes::DEBUG, "Processed ROUTE: " . var_export($obj, true));
        self::Log(LogTypes::DEBUG, "Processed GET: " . var_export($_GET, true));

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
    public static function templateExists(string $Template): bool
    {
        return file_exists(Themes::getThemeDirectory(). "/templates/$Template");
    }

    /**
     * Truncates a text and appends "..." to the end
     * @param $text
     * @param int $length
     * @param string $ending
     * @param bool $exact
     * @param bool $considerHtml
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


}
