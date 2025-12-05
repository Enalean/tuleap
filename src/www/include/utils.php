<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('session.php');

// This function returns a string of the date $value with the format $format and
// if this date is not set, return the default value $default_value
function format_date(string $format, mixed $value, string $default_value = '-'): string
{
    if ($value == 0) {
        return $default_value;
    }
    if (! is_numeric($value)) {
        return $default_value;
    }
    return date($format, (int) $value);
}

function util_get_user_preferences_export_datefmt()
{
    $fmt    = '';
    $u_pref = user_get_preference('user_csv_dateformat');
    switch ($u_pref) {
        case 'month_day_year':
            $fmt = 'm/d/Y H:i:s';
            break;
        case 'day_month_year':
            $fmt = 'd/m/Y H:i:s';
            break;
        default:
            $fmt = 'm/d/Y H:i:s';
            break;
    }
    return $fmt;
}

// Convert a date in sys_datefmt (Y-M-d H:i ex: 2004-Feb-03 16:13)
// into a Unix time. if string is empty return 0 (Epoch time)
// Returns a list with two values: the unix time and a boolean saying whether the conversion
// went well (true) or bad (false)
function util_importdatefmt_to_unixtime($date)
{
    $time = 0;
    if (! $date || $date == '') {
        return [$time, false];
    }

    if (strstr($date, '/') !== false) {
        [$year, $month, $day, $hour, $minute] = util_xlsdatefmt_explode($date);
        $time                                 = mktime($hour, $minute, 0, $month, $day, $year);

        return [$time, true];
    }

    if (strstr($date, '-') !== false) {
        [$year, $month, $day, $hour, $minute] = util_sysdatefmt_explode($date);
        $time                                 = mktime($hour, $minute, 0, $month, $day, $year);
        return [$time, true];
    }

    return [$time, false];
}

// Explode a date in the form of (m/d/Y H:i or d/m/Y H:i) into its a list of 5 parts (YYYY,MM,DD,H,i)
// if DD and MM are not defined then default them to 1
function util_xlsdatefmt_explode($date)
{
    if ($u_pref = user_get_preference('user_csv_dateformat')) {
    } else {
        $u_pref = PFUser::DEFAULT_CSV_DATEFORMAT;
    }

    $res = preg_match('/\s*(\d+)\/(\d+)\/(\d+) (\d+):(\d+)/', $date, $match);
    if ($res == 0) {
      //if it doesn't work try (n/j/Y) only
        $res = preg_match('/\s*(\d+)\/(\d+)\/(\d+)/', $date, $match);
        if ($res == 0) {
          // nothing is valid return Epoch time
            $year   = '1970';
            $month  = '1';
            $day    = '1';
            $hour   = '0';
            $minute = '0';
        } else {
            if ($u_pref == 'day_month_year') {
                [, $day, $month, $year] = $match;
                $hour                   = '0';
                $minute                 = '0';
            } else {
                [, $month, $day, $year] = $match;
                $hour                   = '0';
                $minute                 = '0';
            }
        }
    } else {
        if ($u_pref == 'day_month_year') {
            [, $day, $month, $year, $hour, $minute] = $match;
        } else {
            [, $month, $day, $year, $hour, $minute] = $match;
        }
    }

    return [$year, $month, $day, $hour, $minute];
}


// Convert a date as used in the bug tracking system and other services (YYYY-MM-DD)
// into a Unix time. if string is empty return 0 (Epoch time)
// Returns a list with two values: the unix time and a boolean saying whether the conversion
// went well (true) or bad (false)
function util_date_to_unixtime($date)
{
    $time = 0;
    if (! $date || $date == '') {
        return [$time, false];
    }

    [$year, $month, $day] = util_date_explode($date);
    $time                 = mktime(0, 0, 0, $month, $day, $year);
    return [$time, true];
}

// Explode a date in the form of (YYYY-MM-DD) into its a list of 3 parts (YYYY,MM,DD)
// if DD and MM are not defined then default them to 1
function util_date_explode($date)
{
    $res = preg_match('/\s*(\d+)-(\d+)-(\d+)/', $date, $match);
    if ($res == 0) {
    // if it doesn't work try YYYY-MM only
        $res = preg_match('/\s*(\d+)-(\d+)/', $date, $match);
        if ($res == 0) {
            // if it doesn't work try YYYY only
            $res = preg_match('/\s*(\d+)/', $date, $match);
            return ['1970', '1', '1'];
        } else {
            [, $year, $month] = $match;
            $day              = '1';
        }
    } else {
        [, $year, $month, $day] = $match;
    }
    return [$year, $month, $day];
}

// Explode a date in the form of (Y-M-d H:i) into its a list of 5 parts (YYYY,MM,DD,H,i)
// if DD and MM are not defined then default them to 1
function util_sysdatefmt_explode($date)
{
    $months = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];

    $res = preg_match('/\s*(\d+)-(.+)-(\d+) (\d+):(\d+)/', $date, $match);
    if ($res == 0) {
      //if it doesn't work try (Y-M-d) only
        $res = preg_match('/\s*(\d+)-(.+)-(\d+)/', $date, $match);
        if ($res == 0) {
          // if it doesn't work try Y-M only
            $res = preg_match('/\s*(\d+)-(.+)/', $date, $match);
            if ($res == 0) {
         // if it doesn't work try YYYY only
                $res = preg_match('/\s*(\d+)/', $date, $match);
                if ($res == 0) {
                       // nothing is valid return Epoch time
                       $year = '1970';
                    $month   = '1';
                    $day     = '1';
                    $hour    = '0';
                    $minute  = '0';
                } else {
                          [, $year] = $match;
                    $month          = '1';
                    $day            = '1';
                    $hour           = '0';
                    $minute         = '0';
                }
            } else {
                [, $year, $month] = $match;
                $day              = '1';
                $hour             = '0';
                $minute           = '0';
            }
        } else {
            [, $year, $month, $day] = $match;
            $hour                   = '0';
            $minute                 = '0';
        }
    } else {
        [, $year, $month, $day, $hour, $minute] = $match;
    }

    return [$year, getMonth($month, $ok), $day, $hour, $minute];
}

//accept now month either in format Jan-Dec or 1-12
function getMonth($month, &$ok)
{
    $months = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];
    if (array_key_exists($month, $months)) {
        $ok = true;
        return $months[$month];
    } elseif (in_array($month, $months)) {
        $ok = true;
        return $month;
    }
    $ok = false;
    return 1;
}

/**
 * @psalm-pure
 */
function util_unconvert_htmlspecialchars($string)
{
    if (strlen($string) < 1) {
        return '';
    } else {
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace('&quot;', '"', $string);
        $string = str_replace('&gt;', '>', $string);
        $string = str_replace('&lt;', '<', $string);
        $string = str_replace('&amp;', '&', $string);
        return $string;
    }
}

function util_result_column_to_array($result, $col = 0)
{
    /*
        Takes a result set and turns the optional column into
        an array
    */
    $rows = db_numrows($result);

    if ($rows > 0) {
        $arr = [];
        for ($i = 0; $i < $rows; $i++) {
            $arr[$i] = db_result($result, $i, $col);
        }
    } else {
        $arr = [];
    }
    return $arr;
}

function util_make_reference_links($data, $group_id)
{
    if (empty($data)) {
        return $data;
    }
    $reference_manager = ReferenceManager::instance();
    if ($group_id) {
        $reference_manager->insertReferences($data, $group_id);
    }

    return $data;
}

// Clean up email address (remove starting and ending spaces),replace semicolon by comma and put to lower
// case
function util_cleanup_emails($addresses)
{
    $addresses = preg_replace('/\s+[,;]/', ',', $addresses);
    $addresses = preg_replace('/[,;]\s+/', ',', $addresses);
    $addresses = str_replace(';', ',', $addresses);
    return strtolower(rtrim(trim($addresses)));
}

// One Email Verification
function validate_email($address)
{
    $rule = new Rule_Email();
    return $rule->isValid($address);
}

/**
     * Return if the email addresses are valid
     *
     * @param arr_email: list of email addresses
     * @param message (OUT): error message if an error is found
     * @param strict (IN): Parametrer for user_finder function
     *
     * @return bool
     */
function util_validateCCList(&$arr_email, &$message, $strict = false)
{
    global $Language;
    $valid    = true;
    $message  = '';
    $purifier = Codendi_HTMLPurifier::instance();
    foreach ($arr_email as $key => $cc) {
        // Make sure that the address is valid
        $ref = util_user_finder($cc, $strict);
        if (empty($ref)) {
            $valid    = false;
            $message .= "'" . $purifier->purify($cc) . "'<br>";
            continue;
        } else {
            $arr_email[$key] = $ref;
        }
    }

    if (! $valid) {
        $message = $Language->getText('include_utils', 'address_problem') . ':'
            . "<blockquote>$message</blockquote>"
            . $Language->getOverridableText('include_utils', 'email_explain');
    }

    return $valid;
}


/**
 * Try to find the best user identifier for a given identifier.
 *
 * The best (from Codendi point of view) user identifier is the Codendi
 * user_name. But people don't remember Codendi user_name. A given user can
 * reference another user with his email, codendi user_name, ldap uid, ldap
 * common name.
 * This function returns the best identifier:
 * - First ask to plugins (mainly LDAP) if they know a codendi user with this
 *   identifier
 * - If no user found by plugin, test if identifier is a valid codendi username
 * - Otherwise, if not in strict mode (ie. doesn't mandate a valid codendi user)
 *   test if its a valid email address.
 * - Else, return an empty string (ie. not a valid identifier)
 *
 * @param String  $ident (IN)      A user identifier
 * @param bool $strict (IN)     If strict mode is enabled only Codendi user and ldap valid
 * entries are allowed. Otherwise, return an empty string
 *
 * @return String
 */
function util_user_finder($ident, $strict = true)
{
    $ident = trim($ident);
    $user  = UserManager::instance()->findUser($ident);
    if ($user) {
        return $user->getUserName();
    } else {
        // Neither Plugins nor Codendi found a valid user with this
        // identifier. If allowed, return the identifier as email address
        // if the identifier is a valid email address.
        if (! $strict) {
            if (validate_email($ident)) {
                return $ident;
            }
        }
    }
    return '';
}

// This function get the image file for the theme.
// The theme may be specified as an optional second parameter.
// If no theme parameter is given, the current global theme is used.
// If $absolute is true, then the generated path will be absolute,
// otherwise it is relative to $sys_urlroot.
function util_get_image_theme($fn, $the_theme = false, $absolute = false)
{
    $path = util_get_dir_image_theme($the_theme);
    if ($absolute) {
        $path = ForgeConfig::get('sys_urlroot') . $path;
    }
    return $path . $fn;
}

// this function get the image directory for the theme
// (either given or current theme)
function util_get_dir_image_theme($the_theme = false)
{
    if (! $the_theme) {
        $the_theme = ForgeConfig::get('sys_user_theme');
    }

    return '/themes/' . $the_theme . '/images/';
}

// Format a size in byte into a size in Mb
function formatByteToMb($size_byte)
{
    return intval($size_byte / (1024 * 1024));
}

/**
 * getStringFromServer - get a string from Server environment
 *
 * @param string $key key of the wanted value
 * @return string the value
 */
function getStringFromServer($key)
{
    if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
    } else {
            return '';
    }
}

function util_return_to($url)
{
    $request       = \Tuleap\HTTPRequest::instance();
    $event_manager = EventManager::instance();
    $url_redirect  = new URLRedirect($event_manager);
    $return_to     = $request->get('return_to');
    $GLOBALS['Response']->redirect($url_redirect->makeReturnToUrl($url, $return_to));
    exit;
}

/**
 * Split an HTTP request in 2 parts: headers and body
 *
 * Header/body separator is CRLF
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html
 *
 * @param string $content
 * @return array
 */
function http_split_header_body($content)
{
    $body_header_separator = "\r\n\r\n";
    $end_of_headers        = strpos($content, $body_header_separator);
    $beginning_of_body     = $end_of_headers + strlen($body_header_separator);
    $headers               = substr($content, 0, $end_of_headers);
    $body                  = substr($content, $beginning_of_body);
    return [$headers, $body];
}
