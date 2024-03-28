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

namespace Tuleap\Http\Client;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyString;

#[ConfigKeyCategory('Outbound HTTP requests')]
final class OutboundHTTPRequestProxy
{
    #[ConfigKey('Proxy used by outbound HTTP requests')]
    #[ConfigKeyString('')]
    public const PROXY = 'sys_proxy';

    #[ConfigKey('Bypass SSRF filtering proxy (smokescreen). Only use in case of emergency.')]
    #[ConfigKeyString(self::FILTERING_PROXY_ENABLED)]
    #[ConfigKeyHidden]
    public const FILTERING_PROXY_USAGE    = 'filtering_proxy_usage';
    public const FILTERING_PROXY_ENABLED  = 'enabled';
    public const FILTERING_PROXY_DISABLED = 'disabled';

    private const DEFAULT_FILTERING_PROXY = 'localhost:4750';

    private function __construct()
    {
    }

    public static function getProxy(): string
    {
        if (self::isProxyDefinedByAdministrators()) {
            return self::getProxyDefinedByAdministrators();
        }

        if (self::isFilteringProxyEnabled()) {
            return self::DEFAULT_FILTERING_PROXY;
        }

        return '';
    }

    public static function isFilteringProxyEnabled(): bool
    {
        return ! self::isProxyDefinedByAdministrators() && \ForgeConfig::get(self::FILTERING_PROXY_USAGE) !== self::FILTERING_PROXY_DISABLED;
    }

    public static function isProxyDefinedByAdministrators(): bool
    {
        return self::getProxyDefinedByAdministrators() !== '';
    }

    private static function getProxyDefinedByAdministrators(): string
    {
        return trim(\ForgeConfig::get(self::PROXY, ''));
    }
}
