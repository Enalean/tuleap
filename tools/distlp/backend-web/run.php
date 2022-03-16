<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Symfony\Component\Process\Process;
use TuleapCfg\Command\SiteDeploy\FPM\FPMSessionRedis;
use TuleapCfg\Command\SiteDeploy\FPM\SiteDeployFPM;
use TuleapCfg\Command\SiteDeploy\Nginx\SiteDeployNginx;

require_once __DIR__ . '/../../../src/vendor/autoload.php';

// Make all warnings or notices fatal
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    die("$errno $errstr $errfile $errline");
}, E_ALL | E_STRICT);

$logger = new \Monolog\Logger('backend-svn');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(STDOUT));

$redis_conf_file = '/etc/tuleap/conf/redis.inc';
$fpm             = new SiteDeployFPM(
    $logger,
    'codendiadm',
    true,
    new FPMSessionRedis(
        $redis_conf_file,
        'codendiadm',
        'redis',
    ),
    SiteDeployFPM::PHP80_DST_CONF_DIR,
    SiteDeployFPM::PHP80_SRC_CONF_DIR,
    [],
);
$nginx           = new SiteDeployNginx($logger, '/usr/share/tuleap', '/etc/nginx', 'reverse-proxy', true);

$fpm->forceDeploy();
$nginx->configure();

if (isset($argv[1]) && $argv[1] === 'test') {
    try {
        $process = new Process(['/usr/share/tuleap/tools/distlp/backend-web/prepare-instance.sh']);
        $process
            ->setTimeout(0)
            ->mustRun();
    } catch (Exception $e) {
        die($e->getMessage());
    }
}
