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

use ForgeConfig;

/**
 * Abstract service control from system implementation
 *
 * It will favor systemd if present so it should work out-of-the box on Centos7 and RHEL7
 */
class ServiceControl
{
    public const SYSTEMD     = 'systemd';
    public const INITV       = 'initv';
    public const SUPERVISORD = 'supervisord';

    /**
     * Init mode for the platform (systemd, initv, supervisord)
     * @tlp-config-key
     */
    public const FORGECONFIG_INIT_MODE = 'init_mode';

    public function getInitMode()
    {
        if (ForgeConfig::get(self::FORGECONFIG_INIT_MODE) === self::SUPERVISORD) {
            return self::SUPERVISORD;
        }
        if (is_executable('/usr/bin/systemctl')) {
            return self::SYSTEMD;
        }
        if (is_executable('/sbin/service')) {
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
