<?php
# Must be site admin to access
require($DOCUMENT_ROOT.'/include/pre.php');
session_require(array('group'=>1,'admin_flags'=>'A'));

phpinfo()?>
