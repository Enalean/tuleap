<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * Create URL for user's profile page
 *
 * @param string $username
 * @param int $user_id
 * @return string URL
 */
function util_make_url_u ($username, $user_id) {
    return util_make_url ("/users/$username/");
}


/**
 * Create URL for a project's page
 *
 * @param string $groupame
 * @param int $group_id
 * @return string
 */
function util_make_url_g ($groupame, $group_id) {
	if (isset ($GLOBALS['sys_noforcetype']) && $GLOBALS['sys_noforcetype']) {
		return util_make_url ("/project/?group_id=$group_id");
	} else {
		return util_make_url ("/projects/$groupame/");
	}
}

/**
 * Constructs the forge's URL prefix out of forge_get_config('url_prefix')
 *
 * @return string
 */
function normalized_urlprefix() {
	$prefix = forge_get_config('url_prefix') ;
	$prefix = preg_replace ("/^\//", "", $prefix) ;
	$prefix = preg_replace ("/\/$/", "", $prefix) ;
	$prefix = "/$prefix/" ;
	if ($prefix == '//')
		$prefix = '/' ;
	return $prefix ;
}

/**
 * Return URL prefix (http:// or https://)
 *
 * @param       string  $prefix (optional) : 'http' or 'https' to force it
 * @return	string	URL prefix
 */
function util_url_prefix($prefix = '') {
	if ($prefix == 'http' || $prefix == 'https' ) {
		return $prefix . '://';
	}
	else {
		if (forge_get_config('use_ssl')) {
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
 * @return	string base URL
 */
function util_make_base_url($prefix = '') {
	$url = util_url_prefix($prefix);
	$url .= forge_get_config('web_host') ;
	if (forge_get_config('https_port') && (forge_get_config('https_port') != 443)) {
		$url .= ":".forge_get_config('https_port') ;
	}
	return $url;
}

/**
 * Construct full URL from a relative path
 *
 * @param	string	$path (optional)
 * @param       string  $prefix (optional) : 'http' or 'https' to force it
 * @return	string	URL
 */
function util_make_url($path = '', $prefix = '') {
	$url = util_make_base_url($prefix).util_make_uri($path) ;
	return $url;
}

/**
 * Create a HTML link to a project's page
 * @param string $groupame
 * @param int $group_id
 * @param string $text
 * @return string
 */
function util_make_link_g ($groupname, $group_id,$text) {
	$hook_params =array();
	$hook_params['resource_type']  = 'group';
	$hook_params['group_name'] = $groupname;
	$hook_params['group_id'] = $group_id;
	$hook_params['link_text'] = $text;
	$hook_params['group_link'] = '';
	plugin_hook_by_reference('project_link_with_tooltip', $hook_params);
	if($hook_params['group_link'] != '') {
		return $hook_params['group_link'];
	}

	return '<a href="' . util_make_url_g ($groupname, $group_id) . '">' . $text . '</a>' ;
}

/**
 * Find the relative URL from full URL, removing http[s]://forge_name[:port]
 *
 * @param	string	URL
 */
function util_find_relative_referer($url) {
	$relative_url = str_replace(util_make_base_url(), '', $url);
	//now remove previous feedback, error_msg or warning_msg
	$relative_url = preg_replace('/&error_msg=.*&/', '&', $relative_url);
	$relative_url = preg_replace('/&warning_msg=.*&/', '&', $relative_url);
	$relative_url = preg_replace('/&feedback=.*&/', '&', $relative_url);
	$relative_url = preg_replace('/&error_msg=.*/', '', $relative_url);
	$relative_url = preg_replace('/&warning_msg=.*/', '', $relative_url);
	$relative_url = preg_replace('/&feedback=.*/', '', $relative_url);
	return $relative_url;
}

/**
 * Construct proper (relative) URI (prepending prefix)
 *
 * @param string $path
 * @return string URI
 */
function util_make_uri($path) {
	$path = preg_replace('/^\//', '', $path);
	$uri = normalized_urlprefix();
	$uri .= $path;
	return $uri;
}

