<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace TuleapCfg\Command\SiteDeploy\Realtime;

use Psl\File\WriteMode;

final class SiteDeployRealtime
{
    public function __construct()
    {
    }

    public function deploy(): void
    {
        $file = '/var/lib/tuleap/tuleap-realtime-key';

        $application_user_name = \ForgeConfig::getApplicationUserLogin();
        $application_user      = posix_getpwnam($application_user_name);
        if ($application_user === false) {
            throw new \RuntimeException(sprintf('User %s does not exist', $application_user_name));
        }

        \Psl\Filesystem\create_file($file);
        \Psl\Filesystem\change_permissions($file, 0600);
        \Psl\Filesystem\change_owner($file, $application_user['uid']);
        \Psl\Filesystem\change_group($file, $application_user['gid']);
        \Psl\File\write($file, 'PRIVATE_KEY=' . base64_encode(random_bytes(64)), WriteMode::Truncate);
    }
}
