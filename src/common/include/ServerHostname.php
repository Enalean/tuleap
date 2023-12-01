<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap;

use Tuleap\Config\ConfigCannotBeModifiedYet;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyString;

final class ServerHostname
{
    #[ConfigKey('The default Tuleap domain')]
    #[ConfigKeyHelp(<<<EOT
    This is used where ever the "naked" form of the Tuleap domain might be used.
    This is also used as the default name for the Web server using
    the standard https protocols. You can also define a specific port number (useful for test servers - default 443)
    EOT)]
    #[ConfigCannotBeModifiedYet]
    #[ConfigKeyString]
    public const DEFAULT_DOMAIN = 'sys_default_domain';

    private function __construct()
    {
    }

    public static function hostnameWithHTTPSPort(): string
    {
        return \ForgeConfig::get(self::DEFAULT_DOMAIN, '');
    }

    /**
     * @psalm-return non-empty-string
     */
    public static function HTTPSUrl(): string
    {
        return 'https://' . self::hostnameWithHTTPSPort();
    }

    public static function rawHostname(): string
    {
        [$host] = explode(':', self::hostnameWithHTTPSPort());
        return $host;
    }
}
