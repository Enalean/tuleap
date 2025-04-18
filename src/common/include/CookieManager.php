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
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyString;

class CookieManager
{
    #[ConfigKey('The default Tuleap domain')]
    #[ConfigKeyString('TULEAP')]
    public const PREFIX = 'sys_cookie_prefix';

    private const PREFIX_HOST = '__Host-';

    public function setCookie(string $name, string $value, int $expire = 0): void
    {
        $cookie_name = self::getCookieName($name);
        setcookie(
            $cookie_name,
            $value,
            [
                'path'     => '/',
                'expires'  => $expire,
                'httponly' => true,
                'secure'   => true,
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[$cookie_name] = $value;
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
        $cookie_name   = "{$cookie_prefix}_{$name}";

        return self::PREFIX_HOST . $cookie_name;
    }
}
