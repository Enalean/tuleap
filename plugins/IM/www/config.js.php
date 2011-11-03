<?php

require_once('pre.php');

echo 'var XMPPDOMAIN = "'.$GLOBALS['sys_default_domain'].'";'; // domain name of jabber service to be used

echo 'var XMPPDOMAINSSL = "'.$GLOBALS['sys_https_host'].'";'; // domain name of jabber service to be used

echo 'var GROUP_ID = "'.$request->get('group_id').'";'; // the current group_id

?>
