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

/**
 * Abstract service control from system implementation
 *
 * It will favor systemd if present so it should work out-of-the box on Centos7 and RHEL7
 */
class ServiceControl
{
    const SYSTEMD = 'systemd';
    const INITV   = 'initv';

    public function getInitMode()
    {
        if (is_executable('/usr/bin/systemctl')) {
            return self::SYSTEMD;
        } elseif (is_executable('/sbin/service')) {
            return self::INITV;
        }
    }

    public function execute($service, $action)
    {
        switch ($this->getInitMode()) {
            case self::SYSTEMD:
                $this->systemctl($service, $action);
                break;

            case self::INITV:
                $this->service($service, $action);
                break;
        }
    }

    public function systemctl($service, $action)
    {
        (new \System_Command())->exec(sprintf('/usr/bin/systemctl %s %s', escapeshellarg($action), escapeshellarg($service)));
    }

    public function service($service, $action)
    {
        (new \System_Command())->exec(sprintf('/sbin/service %s %s', escapeshellarg($service), escapeshellarg($action)));
    }
}
