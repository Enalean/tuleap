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

namespace Tuleap\Configuration\Nginx;

use Psr\Log\LoggerInterface;

class BackendWeb
{
    private $tuleap_base_dir;
    private $nginx_base_dir;
    private $server_name;
    private $common;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, $tuleap_base_dir, $nginx_base_dir, $server_name)
    {
        $this->logger          = $logger;
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
        $this->server_name     = $server_name;
        $this->common          = new Common($this->logger, $tuleap_base_dir, $nginx_base_dir);
    }

    public function configure()
    {
        $this->common->deployConfigurationChunks();
        $this->common->deployMainNginxConf();
        $this->common->replacePlaceHolderInto(
            $this->tuleap_base_dir . '/tools/distlp/backend-web/nginx/tuleap.conf',
            $this->nginx_base_dir . '/conf.d/tuleap.conf',
            array(
                '%sys_default_domain%'
            ),
            array(
                $this->server_name
            )
        );
    }
}
