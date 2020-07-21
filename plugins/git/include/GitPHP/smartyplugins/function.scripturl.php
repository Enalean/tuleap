<?php
/**
 * scripturl
 *
 * Smarty function to get the full url of the current script
 *
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
    if (Tuleap\Git\GitPHP\Config::GetInstance()->HasKey('self')) {
        $selfurl = Tuleap\Git\GitPHP\Config::GetInstance()->GetValue('self');
        if (! empty($selfurl)) {
            if (substr($selfurl, -4) != '.php') {
                $selfurl = Tuleap\Git\GitPHP\Util::AddSlash($selfurl);
            }
            return $selfurl;
        }
    }

    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
        $scriptstr = 'https://';
    } else {
        $scriptstr = 'http://';
    }

    $scriptstr .= $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    return $scriptstr;
}
