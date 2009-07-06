<?php
/*
 *  i18n.initlocale.php
 *  gitphp: A PHP git repository browser
 *  Component: i18n - load locale
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('i18n.lookupstring.php');

function initlocale($locale = "en_US")
{
	global $tpl;

	if (!include(GITPHP_LOCALE_DIR . $locale . ".php"))
		include(GITPHP_LOCALE_DIR . "en_US.php");

	if (isset($strings)) {
		$tpl->assign("localize",$strings);
	}

	return $strings;
}

?>
