#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

$password_hasher_short_options = 'p:';
$password_hasher_long_options  = ['password:'];

$options  = getopt($password_hasher_short_options, $password_hasher_long_options);
$password = false;
foreach ($options as $option => $value) {
    switch ($option) {
        case 'p':
        case 'password':
            $password = $value;
            break;
    }
}

require_once __DIR__ . '/../../src/vendor/autoload.php';

$console_output = new \Symfony\Component\Console\Output\ConsoleOutput();

if ($password === false) {
    $console_output->writeln("Usage: password_hasher.php --password='pass'");
    exit(1);
}

$password_handler = PasswordHandlerFactory::getPasswordHandler();

$concealed_password = new \Tuleap\Cryptography\ConcealedString($password);
sodium_memzero($password);

$hashed_password = $password_handler->computeHashPassword($concealed_password);

$console_output->write(\Symfony\Component\Console\Formatter\OutputFormatter::escape($hashed_password));
