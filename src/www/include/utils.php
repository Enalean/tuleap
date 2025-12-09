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
