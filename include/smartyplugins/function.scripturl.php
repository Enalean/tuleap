<?php
/*
 *  function.scripturl.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - get running script url
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

function smarty_function_scripturl($params, &$smarty)
{
	if (GitPHP_Config::GetInstance()->HasKey('self')) {
		$selfurl = GitPHP_Config::GetInstance()->GetValue('self');
		if (!empty($selfurl)) {
			if (substr($selfurl, -4) != '.php') {
				$selfurl = GitPHP_Util::AddSlash($selfurl);
			}
			return $selfurl;
		}
	}

	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'))
		$scriptstr = 'https://';
	else
		$scriptstr = 'http://';

	$scriptstr .= $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

	return $scriptstr;
}

?>
