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

class DistributedSVN
{
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

    public function reverseProxy()
    {
        \ForgeConfig::loadFromFile($this->tuleap_conf_dir . '/conf/local.inc');

        $reverse_proxy = new Configuration\Nginx\ReverseProxy(
            $this->logger,
            $this->tuleap_base_dir,
            '/etc/nginx',
            \ForgeConfig::get('sys_default_domain'),
        );

        $reverse_proxy->configure();
    }

    private function setErrorHandler()
    {
        // Make all warnings or notices fatal
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            die("$errno $errstr $errfile L$errline\n");
        }, E_ALL | E_STRICT);
    }
}
