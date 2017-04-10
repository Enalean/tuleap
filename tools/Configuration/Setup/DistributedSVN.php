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
 *
 */

namespace Tuleap\Configuration\Setup;

use \Tuleap\Configuration;

class DistributedSVN
{
    const OPT_REVERSE_PROXY = 'reverse-proxy';
    const OPT_BACKEND_SVN   = 'backend-svn';

    public function main($argc, $argv)
    {
        $this->setErrorHandler();
        $logger = new Configuration\Logger\Console();

        for ($i = 1; $i < $argc; $i++) {
            switch ($argv[$i]) {
                case self::OPT_REVERSE_PROXY:
                    break;

                case self::OPT_BACKEND_SVN:
                    $this->backendSVN($logger);
                    break;

                default:
                    $this->help();
            }
        }
    }

    private function help()
    {
        echo <<<EOT
/usr/share/tuleap/tools/distlp/setup.sh reverse-proxy|backend-svn

Configuration of Tuleap for with Distributed SVN.

EOT;
        exit(1);
    }

    private function setErrorHandler()
    {
        // Make all warnings or notices fatal
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            die("$errno $errstr $errfile L$errline\n");
        }, E_ALL | E_STRICT);
    }

    private function backendSVN(Configuration\Logger\LoggerInterface $logger)
    {
        $configuration_loader = new Configuration\Etc\LoadLocalInc();
        $vars                 = $configuration_loader->getVars();

        $fpm           = new Configuration\FPM\BackendSVN($logger, $vars->getApplicationBaseDir(), $vars->getApplicationUser());
        $nginx         = new Configuration\Nginx\BackendSVN($logger, $vars->getApplicationBaseDir(), '/etc/nginx', $vars->getServerName());
        $apache_config = new Configuration\Apache\BackendSVN($logger, $vars->getApplicationUser());

        $fpm->configure();
        $nginx->configure();
        $apache_config->configure();
    }
}
