<?php
/*
 *  i18n.lookupstring.php
 *  gitphp: A PHP git repository browser
 *  Component: i18n - look up a string
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

function lookupstring($str, $strings = null)
{
	global $localestrings;

	if (!$str)
		return null;

	if (isset($strings) && (count($strings) > 0)) {
		if (isset($strings[$str]))
			return $strings[$str];
	} else {
		if (isset($localestrings[$str]))
			return $localestrings[$str];
	}

	return $str;
}

?>
