<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use TuleapCfg\Command\ProcessFactory;

class ApacheServiceControl
{
    /**
     * @var ServiceControl
     */
    private $service_control;
    /**
     * @var ProcessFactory
     */
    private $process_factory;

    public function __construct(ServiceControl $service_control, ProcessFactory $process_factory)
    {
        $this->service_control = $service_control;
        $this->process_factory = $process_factory;
    }

    public function reload()
    {
        switch ($this->service_control->getInitMode()) {
            case ServiceControl::SYSTEMD:
                $this->service_control->systemctl('httpd', 'reload');
                break;

            case ServiceControl::INITV:
                $this->service_control->service('httpd', 'graceful');
                break;

            case ServiceControl::SUPERVISORD:
                $this->process_factory->getProcess(['/usr/sbin/httpd', '-k', 'graceful'])->mustRun();
                break;
        }
    }
}
