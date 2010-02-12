<?php
/*
 *  util.script_url.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - get running script url
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

function script_url()
{
	if (Config::GetInstance()->HasKey('self'))
		return Config::GetInstance()->GetValue('self');

	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'))
		$scriptstr = "https://";
	else
		$scriptstr = "http://";

	$scriptstr .= $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

	return $scriptstr;
}

?>
