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


$tuleap_user        = new \Tuleap\Configuration\ApplicationUserFromPath('tuleap', '/data/etc/tuleap');
$default_paths      = new \Tuleap\Configuration\DefaultPaths('tuleap');
$links              = new \Tuleap\Configuration\Docker\LinkFromDataVolume();
$supervisord        = new \Tuleap\Configuration\Docker\BackendSVN('/usr/share/tuleap');
$fpm                = new \Tuleap\Configuration\FPM\BackendSVN('/usr/share/tuleap', 'tuleap');
$nginx              = new \Tuleap\Configuration\Nginx\BackendSVN('/usr/share/tuleap', '/etc/nginx', 'tuleap-web.tuleap-aio-dev.docker');
$tuleap_auth_module = new \Tuleap\Configuration\Apache\TuleapAuthModule('/usr/share/tuleap');
$apache_config      = new \Tuleap\Configuration\Apache\BackendSVN('tuleap');

$tuleap_user->configure();
$default_paths->configure();
$links->configure();
$fpm->configure();
$nginx->configure();
$tuleap_auth_module->configure();
$apache_config->configure();
$supervisord->configure();
