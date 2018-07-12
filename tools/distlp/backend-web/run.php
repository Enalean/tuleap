<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once '/usr/share/php/Zend/autoload.php';

$loader = new Zend\Loader\StandardAutoloader(
    array(
        'namespaces' => array(
            'Tuleap\Configuration' => '/usr/share/tuleap/tools/Configuration',
        )
    )
);
$loader->register();

// Make all warnings or notices fatal
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    die("$errno $errstr $errfile $errline");
}, E_ALL | E_STRICT);

$logger = new Tuleap\Configuration\Logger\Console();

$fpm   = new Tuleap\Configuration\FPM\TuleapWeb($logger, 'codendiadm', true);
$nginx = new \Tuleap\Configuration\Nginx\BackendWeb($logger, '/usr/share/tuleap', '/etc/opt/rh/rh-nginx18/nginx', 'reverse-proxy');
$rabbitmq = new Tuleap\Configuration\RabbitMQ\BackendWeb('codendiadm');

$fpm->configure();
$nginx->configure();
$rabbitmq->configure();

if (isset($argv[1]) && $argv[1] == 'test') {
    try {
        $exec = new \Tuleap\Configuration\Common\Exec();
        $exec->command("/usr/share/tuleap/src/utils/tuleap import-project-xml -u admin --automap=no-email,create:A -i /usr/share/tuleap/tests/e2e/_fixtures/svn_project_01 --use-lame-password");
        $exec->command("/usr/share/tuleap/src/utils/tuleap import-project-xml -u admin --automap=no-email,create:A -i /usr/share/tuleap/tests/e2e/_fixtures/permission_project_02 --use-lame-password");
        $exec->command("/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/svnroot_push.php");
    } catch (Exception $e) {
        die($e->getMessage());
    }
    file_put_contents('/etc/tuleap/conf/local.inc', preg_replace('/\$sys_trusted_proxies = \'\'/', '$sys_trusted_proxies = \''.gethostbyname('reverse-proxy').'\'', file_get_contents('/etc/tuleap/conf/local.inc')));
}
