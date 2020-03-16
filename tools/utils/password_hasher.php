#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

$password_hasher_short_options = 'p:u';
$password_hasher_long_options  = array('password:', 'unix');

$options  = getopt($password_hasher_short_options, $password_hasher_long_options);
$password = false;
$is_unix  = false;
foreach ($options as $option => $value) {
    switch ($option) {
        case 'p':
        case 'password':
            $password = $value;
            break;
        case 'u':
        case 'unix':
            $is_unix = true;
            break;
    }
}

if ($password === false) {
    echo("Usage: password_hasher.php --password='pass'

    Options:

    -u, --unix Generate a UNIX compatible password\n");
    exit(1);
}

require_once __DIR__ . '/../../src/vendor/autoload.php';

$password_handler = PasswordHandlerFactory::getPasswordHandler();

if ($is_unix) {
    $hashed_password = $password_handler->computeUnixPassword($password);
} else {
    $hashed_password = $password_handler->computeHashPassword($password);
}

echo($hashed_password);
