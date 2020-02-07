<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

class LogrotateDeployer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function deployLogrotate()
    {
        $httpd_logrotate = '/etc/logrotate.d/httpd';
        if (! $this->fileContains($httpd_logrotate, '/usr/share/tuleap/src/utils/httpd/postrotate.php')) {
            $this->logger->info('Deploy logrotate');
            if (file_exists($httpd_logrotate)) {
                unlink($httpd_logrotate);
            }
            copy('/usr/share/tuleap/src/etc/logrotate.httpd.conf', $httpd_logrotate);
            chmod($httpd_logrotate, 0644);
        } else {
            $this->logger->warning('Logrotate contains reference to postrotate.php, skip configuration');
        }
    }

    private function fileContains($filepath, $needle)
    {
        if (file_exists($filepath)) {
            return strpos(file_get_contents($filepath), $needle) !== false;
        }
        return false;
    }
}
