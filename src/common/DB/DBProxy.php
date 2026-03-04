<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\DB;

use Tuleap\Config\ConfigCannotBeModifiedYet;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\ConfigKeyString;

final class DBProxy
{
    #[ConfigKey('Database proxy (ProxySQL) server hostname or IP address')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyString('localhost')]
    #[ConfigKeyHidden]
    public const string PROXY_HOST = 'db_proxy_host';

    #[ConfigKey('Database proxy (ProxySQL) server port')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyInt(DBConfig::DEFAULT_MYSQL_PORT)]
    #[ConfigKeyHidden]
    public const string PROXY_PORT = 'db_proxy_port';

    #[ConfigKey('List of pages that should go through database proxy')]
    #[ConfigCannotBeModifiedYet('/etc/tuleap/conf/database.inc')]
    #[ConfigKeyString]
    #[ConfigKeyHidden]
    public const string PROXY_PAGES = 'db_proxy_pages';

    private static ?self $instance      = null;
    private(set) bool $should_use_proxy = false;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public function activateProxyForMatchingURLS(): void
    {
        if (isset($_SERVER['REQUEST_URI']) && is_string(\ForgeConfig::get(self::PROXY_PAGES))) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if ($path === false) {
                return;
            }
            $list_of_pages = array_map(
                function (string $page) {
                    $trimmed = trim($page);
                    if ($trimmed === '/') {
                        return '/';
                    }
                    return rtrim($trimmed, '/');
                },
                explode(
                    ',',
                    \ForgeConfig::get(self::PROXY_PAGES)
                )
            );
            if (in_array($path, $list_of_pages, true)) {
                $this->should_use_proxy = true;
            }
        }
    }
}
