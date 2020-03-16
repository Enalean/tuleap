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

namespace Tuleap\Configuration\Nginx;

use Psr\Log\LoggerInterface;
use Tuleap\Configuration\Logger\Wrapper;

class BackendSVN
{
    private $tuleap_base_dir;
    private $nginx_base_dir;
    private $server_name;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, $tuleap_base_dir, $nginx_base_dir, $server_name)
    {
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
        $this->server_name     = $server_name;
        $this->logger          = new Wrapper($logger, 'Nginx');
    }

    public function configure()
    {
        $this->replaceDefaultNginxConfig();
        $this->deployBackendSVNConfig();
    }

    private function replaceDefaultNginxConfig()
    {
        if (file_exists($this->nginx_base_dir . '/nginx.conf.orig')) {
            $this->logger->warning($this->nginx_base_dir . '/nginx.conf.orig already exists, skip nginx configuration');
        }
        $this->backupOriginalFile($this->nginx_base_dir . '/nginx.conf');
        copy($this->tuleap_base_dir . '/tools/distlp/backend-svn/nginx.conf', $this->nginx_base_dir . '/nginx.conf');
    }

    private function deployBackendSVNConfig()
    {
        $template = file_get_contents($this->tuleap_base_dir . '/tools/distlp/backend-svn/backend-svn.conf');
        $searches = array(
            '%sys_default_domain%',
        );
        $replaces = array(
            $this->server_name,
        );

        $conf = str_replace($searches, $replaces, $template);
        file_put_contents($this->nginx_base_dir . '/conf.d/backend-svn.conf', $conf);
    }

    private function backupOriginalFile($file)
    {
        if (! file_exists($file . '.orig')) {
            copy($file, $file . '.orig');
        }
    }
}
