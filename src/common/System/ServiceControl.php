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
use Tuleap\Config\ConfigKey;

/**
 * Abstract service control from system implementation
 */
class ServiceControl
{
    public const SYSTEMD     = 'systemd';
    public const SUPERVISORD = 'supervisord';

    #[ConfigKey('Init mode for the platform (systemd, supervisord)')]
    public const FORGECONFIG_INIT_MODE = 'init_mode';

    public function getInitMode()
    {
        if (ForgeConfig::get(self::FORGECONFIG_INIT_MODE) === self::SUPERVISORD) {
            return self::SUPERVISORD;
        }
        if (is_executable('/usr/bin/systemctl')) {
            return self::SYSTEMD;
        }
    }

    public function execute($service, $action)
    {
        switch ($this->getInitMode()) {
            case self::SYSTEMD:
                $this->systemctl($service, $action);
                break;
        }
    }

    public function systemctl($service, $action)
    {
        (new \System_Command())->exec(sprintf('/usr/bin/systemctl %s %s', escapeshellarg($action), escapeshellarg($service)));
    }
}
