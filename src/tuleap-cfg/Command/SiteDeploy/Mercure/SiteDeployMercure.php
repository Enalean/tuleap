<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy\Mercure;

final class SiteDeployMercure
{
    public function __construct()
    {
    }

    public function deploy(): void
    {
        $file               = '/etc/tuleap/conf/mercure.env';
        $private_key        = base64_encode(random_bytes(128));
        $private_key_format = 'MERCURE_KEY=' . $private_key . PHP_EOL;
        $return_val         = @file_put_contents($file, $private_key_format);
        if (! $return_val) {
            throw new \RuntimeException($file . ' write failed with user (uid ' . posix_getuid() . ')');
        }
    }
}
