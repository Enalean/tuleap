<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Tuleap\Configuration;
use Tuleap\Configuration\Apache\LogrotateDeployer;

class DistributedSVN
{
    public const OPT_REVERSE_PROXY = 'reverse-proxy';
    public const OPT_BACKEND_SVN   = 'backend-svn';

    public const PID_ONE_SYSTEMD     = 'systemd';
    public const PID_ONE_SUPERVISORD = 'supervisord';

    /**
     * @var LoggerInterface
     */
    private $logger;
    private $tuleap_base_dir = '/usr/share/tuleap';
    private $tuleap_conf_dir = '/etc/tuleap';

    public function __construct()
    {
        $this->setErrorHandler();
        $this->logger = new Configuration\Logger\Console();
    }

    public function setTuleapBaseDir($dir)
    {
        $this->tuleap_base_dir = $dir;
    }

    public function setTuleapConfDir($dir)
    {
        $this->tuleap_conf_dir = $dir;
    }

    public function main(array $options)
    {
        if (isset($options['h']) || isset($options['help'])) {
            $this->help();
            exit(0);
        }
        if (isset($options['tuleap-base-dir'])) {
            $this->setTuleapBaseDir($options['tuleap-base-dir']);
        }
        if (isset($options['tuleap-conf-dir'])) {
            $this->setTuleapConfDir($options['tuleap-conf-dir']);
        }
        if (isset($options['module'])) {
            switch ($options['module']) {
                case self::OPT_REVERSE_PROXY:
                    $this->reverseProxy();
                    exit(0);
                    break;

                case self::OPT_BACKEND_SVN:
                    $this->backendSVN();
                    exit(0);
                    break;
            }
        }
        $this->help();
        exit(1);
    }

    public function backendSVN()
    {
        $vars = $this->getVars();

        $fpm           = new Configuration\FPM\BackendSVN($this->logger, $vars->getApplicationBaseDir(), $vars->getApplicationUser());
        $nginx         = new Configuration\Nginx\BackendSVN($this->logger, $vars->getApplicationBaseDir(), '/etc/nginx', $vars->getServerName());
        $apache_config = new Configuration\Apache\BackendSVN($this->logger, $vars->getApplicationUser(), new LogrotateDeployer($this->logger));

        $fpm->configure();
        $nginx->configure();
        $apache_config->configure();
    }

    public function reverseProxy()
    {
        $vars = $this->getVars();
        $reverse_proxy        = new Configuration\Nginx\ReverseProxy(
            $this->logger,
            $this->tuleap_base_dir,
            '/etc/nginx',
            $vars->getServerName()
        );

        $reverse_proxy->configure();
    }

    private function getVars()
    {
        $configuration_loader = new Configuration\Etc\LoadLocalInc($this->tuleap_conf_dir, $this->tuleap_base_dir);
        return $configuration_loader->getVars();
    }

    private function help()
    {
        echo <<<EOT

Usage: /usr/share/tuleap/tools/distlp/setup.sh OPTIONS --module=reverse-proxy|backend-svn

Configuration of Tuleap for with Distributed SVN.

Options:
    --tuleap-base-dir=/path      Where Tuleap sources are available
    --tuleap-conf-dir=/path      Where Tuleap configuration directory is available

EOT;
    }

    private function setErrorHandler()
    {
        // Make all warnings or notices fatal
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            die("$errno $errstr $errfile L$errline\n");
        }, E_ALL | E_STRICT);
    }
}
