<?php
/**
 * scripturl
 *
 * Smarty function to get the full url of the current script
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Smarty
 */

/**
 * scripturl smarty function
 *
 * @param array $params function parameters
 * @param mixed $smarty smarty object
 * @return string script url
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
