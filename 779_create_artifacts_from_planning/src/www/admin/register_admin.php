<?php
// the administrator can create a user account; we display the same page as user registration under the admin menu
 require_once('pre.php');
 session_require(array('group'=>'1','admin_flags'=>'A')); 
 require_once 'www/account/register.php';
?>
