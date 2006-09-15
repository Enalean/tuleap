<?php
# Must be site admin to access
require_once('pre.php');
session_require(array('group'=>1,'admin_flags'=>'A'));

phpinfo()?>
