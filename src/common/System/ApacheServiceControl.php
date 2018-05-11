<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\System;

class ApacheServiceControl
{
    /**
     * @var ServiceControl
     */
    private $service_control;

    private $init_usage = true;

    public function __construct(ServiceControl $service_control)
    {
        $this->service_control = $service_control;
    }

    public function disableInitUsage()
    {
        $this->init_usage = false;
        return $this;
    }

    public function reload()
    {
        if ($this->init_usage) {
            switch ($this->service_control->getInitMode()) {
                case ServiceControl::SYSTEMD:
                    $this->service_control->systemctl('httpd', 'reload');
                    break;

                case ServiceControl::INITV:
                    $this->service_control->service('httpd', 'graceful');
                    break;
            }
        } else {
            // Works on both centos6 & centos7
            (new \System_Command())->exec('/usr/sbin/httpd -k graceful');
        }
    }
}
