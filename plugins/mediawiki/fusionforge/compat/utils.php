<?php
/**
 * Copyright (c) Enalean, 2012-2017. All Rights Reserved.
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

/**
 * Constructs the forge's URL prefix out of forge_get_config('url_prefix')
 *
 * @return string
 */
function normalized_urlprefix()
{
    $prefix = forge_get_config('url_prefix');
    $prefix = preg_replace("/^\//", "", $prefix);
    $prefix = preg_replace("/\/$/", "", $prefix);
    $prefix = "/$prefix/";
    if ($prefix == '//') {
        $prefix = '/';
    }
    return $prefix;
}

/**
 * Return URL prefix (http:// or https://)
 *
 * @param       string  $prefix (optional) : 'http' or 'https' to force it
 * @return    string    URL prefix
 */
function util_url_prefix($prefix = '')
{
    if ($prefix == 'http' || $prefix == 'https') {
        return $prefix . '://';
    } else {
        if (ForgeConfig::get('sys_https_host')) {
            return "https://";
        } else {
            return "http://";
        }
    }
}

/**
 * Construct the base URL http[s]://forge_name[:port]
 *
 * @param       string  $prefix (optional) : 'http' or 'https' to force it
 * @return    string base URL
 */
function util_make_base_url($prefix = '')
{
    $url = util_url_prefix($prefix);
    $url .= forge_get_config('web_host');
    if (forge_get_config('https_port') && (forge_get_config('https_port') != 443)) {
        $url .= ":" . forge_get_config('https_port');
    }
    return $url;
}

/**
 * Construct full URL from a relative path
 *
 * @param    string    $path (optional)
 * @param       string  $prefix (optional) : 'http' or 'https' to force it
 * @return    string    URL
 */
function util_make_url($path = '', $prefix = '')
{
    $url = util_make_base_url($prefix) . util_make_uri($path);
    return $url;
}

/**
 * Construct proper (relative) URI (prepending prefix)
 *
 * @param string $path
 * @return string URI
 */
function util_make_uri($path)
{
    $path = preg_replace('/^\//', '', $path);
    $uri = normalized_urlprefix();
    $uri .= $path;
    return $uri;
}
