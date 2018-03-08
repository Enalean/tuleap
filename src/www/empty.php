<?php

require_once 'pre.php';

$public_key = '6EvO0rQQU0ujwRg9xQDJb3X55RpMk1bRX3qfmhgxzZg=';
$secret_key = '8siQhRBt7mxZ4dYVWuHsuu/W8bRbdRayl3kMrMfLv6roS87StBBTS6PBGD3FAMlvdfnlGkyTVtFfep+aGDHNmA==';

$secret_key = base64_decode($secret_key);
$domain     = 'tuleap-web.tuleap-aio-dev.docker';
$username   = 'forge__dynamic_credential-identifier';
$password   = 'password';
$expiration = '2018-03-08T14:32:26+01:00';
var_dump(base64_encode(sodium_crypto_sign_detached($domain . $username . $password . $expiration, $secret_key)));


ey9CGQ2YqUQOTJWYfLM8vJY8ZkgT54By7KMOs2hWuJpC7ba4z4ITndrPhwxWDoojnFcxZe9g6s/xxEWV+btZCA==
