<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap;

use ForgeConfig;

class CookieManager
{
    private const PREFIX_HOST = '__Host-';

    public function setCookie(string $name, string $value, int $expire = 0): void
    {
        setcookie(
            self::getCookieName($name),
            $value,
            [
                'path'     => '/',
                'expires'  => $expire,
                'httponly' => true,
                'secure'   => self::canCookieUseSecureFlag(),
                'samesite' => 'Lax'
            ]
        );
    }

    public static function canCookieUseSecureFlag(): bool
    {
        return (bool) ForgeConfig::get('sys_https_host');
    }

    public function getCookie(string $name): ?string
    {
        return $_COOKIE[self::getCookieName($name)] ?? null;
    }

    public function isCookie(string $name): bool
    {
        return isset($_COOKIE[self::getCookieName($name)]);
    }

    public function removeCookie(string $name): void
    {
        $this->setCookie($name, '');
    }

    public static function getCookieName(string $name): string
    {
        $cookie_prefix = ForgeConfig::get('sys_cookie_prefix');
        $cookie_name   = "${cookie_prefix}_${name}";

        if (! self::canCookieUseSecureFlag()) {
            return $cookie_name;
        }

        return self::PREFIX_HOST . $cookie_name;
    }
}
