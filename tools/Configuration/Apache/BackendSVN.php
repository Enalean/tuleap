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
 */

namespace Tuleap\Configuration\Apache;

use Psr\Log\LoggerInterface;
use Tuleap\Configuration\Logger\Wrapper;

class BackendSVN
{

    private $application_user;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LogrotateDeployer
     */
    private $logrotate_deployer;

    public function __construct(LoggerInterface $logger, $application_user, LogrotateDeployer $logrotate_deployer)
    {
        $this->application_user   = $application_user;
        $this->logger             = new Wrapper($logger, 'Apache');
        $this->logrotate_deployer = $logrotate_deployer;
    }

    public function configure()
    {
        $this->apacheListenOnLocalAsApplicationUser();
        $this->logrotate_deployer->deployLogrotate();
    }

    private function apacheListenOnLocalAsApplicationUser()
    {
        if (file_exists('/etc/httpd/conf/httpd.conf.orig')) {
            $this->logger->warning('/etc/httpd/conf/httpd.conf.orig already exists, skip apache configuration');
            return;
        }
        $this->backupOriginalFile('/etc/httpd/conf/httpd.conf');
        $httpd_conf = file_get_contents('/etc/httpd/conf/httpd.conf.orig');

        $searches = array(
            'Listen 80',
            'User apache',
            'Group apache',
        );
        $replaces = array(
            'Listen 127.0.0.1:8080',
            'User ' . $this->application_user,
            'Group ' . $this->application_user,
        );

        $conf = str_replace($searches, $replaces, $httpd_conf);
        file_put_contents('/etc/httpd/conf/httpd.conf', $conf);
    }


    private function backupOriginalFile($file)
    {
        if (! file_exists($file . '.orig')) {
            copy($file, $file . '.orig');
        }
    }
}
