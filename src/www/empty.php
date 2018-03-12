<?php

require_once 'pre.php';

$public_key = 'fDLMRGYuFzWweXRc9NZkclGwXPRl2+jqN16/ZEEBIBI=';
$private_key = '/PYrnpyejn6lt9nuQNeMnBKMz5cJQlKN2mTBnXASwmh8MsxEZi4XNbB5dFz01mRyUbBc9GXb6Oo3Xr9kQQEgEg==';

$secret_key = base64_decode($private_key);
$domain = 'tuleap-web.tuleap-aio-dev.docker';
$username = 'forge__dynamic_credential-identifier';
$password = 'password';
$expiration = '2018-03-08T14:32:26+01:00';
var_dump(base64_encode(sodium_crypto_sign_detached($domain . $username . $password . $expiration, $secret_key)));
var_dump(base64_encode(sodium_crypto_sign_detached($domain . $username, $secret_key)));