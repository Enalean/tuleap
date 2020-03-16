<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

require_once __DIR__ . '/../../Configuration/vendor/autoload.php';

// Make all warnings or notices fatal
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    die("$errno $errstr $errfile $errline");
}, E_ALL | E_STRICT);

$logger = new \Tuleap\Configuration\Logger\Console();

$fpm      = \Tuleap\Configuration\FPM\TuleapWeb::buildForPHP73($logger, 'codendiadm', true);
$nginx    = new \Tuleap\Configuration\Nginx\BackendWeb($logger, '/usr/share/tuleap', '/etc/nginx', 'reverse-proxy');
$redis = new \Tuleap\Configuration\Redis\BackendWeb('codendiadm');

$fpm->configure();
$nginx->configure();
$redis->configure();

$exec = new \Tuleap\Configuration\Common\Exec();

if (isset($argv[1]) && $argv[1] == 'test') {
    try {
        $exec->command('/usr/share/tuleap/tools/distlp/backend-web/prepare-instance.sh');
    } catch (Exception $e) {
        die($e->getMessage());
    }
    file_put_contents('/etc/tuleap/conf/local.inc', preg_replace('/\$sys_trusted_proxies = \'\'/', '$sys_trusted_proxies = \'' . gethostbyname('reverse-proxy') . '\'', file_get_contents('/etc/tuleap/conf/local.inc')));
}
