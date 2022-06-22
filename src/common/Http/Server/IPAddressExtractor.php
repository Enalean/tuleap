<?php
/**
 * Copyright (c) Enalean 2022-Present. All rights reserved
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

namespace Tuleap\Http\Server;

use ForgeConfig;

final class IPAddressExtractor
{
    private const HEADER_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';
    private const HEADER_REMOTE_ADDR     = 'REMOTE_ADDR';

    public static function getIPAddressFromServerParams(array $server_params): string
    {
        if (self::isFromTrustedProxy($server_params) && isset($server_params[self::HEADER_X_FORWARDED_FOR])) {
            return $server_params[self::HEADER_X_FORWARDED_FOR];
        }
        return $server_params[self::HEADER_REMOTE_ADDR] ?? '';
    }

    private static function isFromTrustedProxy(array $server_params): bool
    {
        if (isset($server_params[self::HEADER_REMOTE_ADDR])) {
            foreach (self::getTrustedProxies() as $proxy) {
                if (self::checkIp4($server_params[self::HEADER_REMOTE_ADDR], $proxy)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Compares two IPv4 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @see Symfony\Component\HttpFoundation\IpUtils @ 3.2-dev (MIT license)
     *
     * @param string $request_ip IPv4 address to check
     * @param string $ip        IPv4 address or subnet in CIDR notation
     *
     * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet.
     */
    private static function checkIp4(string $request_ip, string $ip): bool
    {
        if (false !== strpos($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);

            if ($netmask === '0') {
                // Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
                return (bool) filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }

            if ($netmask < 0 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($request_ip)), sprintf('%032b', ip2long($address)), 0, (int) $netmask);
    }

    private static function getTrustedProxies(): array
    {
        return array_filter(array_map('trim', explode(',', ForgeConfig::get('sys_trusted_proxies', ''))));
    }
}
