<?php 

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once 'pre.php';

$request = HTTPRequest::instance();
$password_sanity_checker = \Tuleap\Password\PasswordSanityChecker::build();
$password_sanity_checker->check($request->get('form_pw'));

echo '['. implode(', ', array_keys($password_sanity_checker->getErrors())) .']';
