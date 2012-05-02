<?php 
require_once('pre.php');
require_once('account.php');

$request =& HTTPRequest::instance();
account_pwvalid($request->get('form_pw'), $errors);
echo '['. implode(', ', array_keys($errors)) .']';

?>
