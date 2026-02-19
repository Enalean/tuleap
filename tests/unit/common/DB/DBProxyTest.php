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

use Override;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DBProxyTest extends TestCase
{
    use ForgeConfigSandbox;

    #[Override]
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        DBProxy::clearInstance();
    }

    #[DataProvider('activateProxyForMatchingURLSDataProvider')]
    #[BackupGlobals(true)]
    public function testActivateProxyForMatchingURLS(string $request_uri, string $proxy_pages, bool $should_use_proxy): void
    {
        \ForgeConfig::set(DBProxy::PROXY_PAGES, $proxy_pages);
        $_SERVER['REQUEST_URI'] = $request_uri;

        DBProxy::instance()->activateProxyForMatchingURLS();
        self::assertEquals($should_use_proxy, DBProxy::instance()->should_use_proxy);
    }

    public static function activateProxyForMatchingURLSDataProvider(): array
    {
        return [
            'requested page is proxified' => [
                'request_uri' => '/',
                'proxy_pages' => '/',
                'should_use_proxy' => true,
            ],
            'requested page is not proxified' => [
                'request_uri' => '/',
                'proxy_pages' => '',
                'should_use_proxy' => false,
            ],
            'subpages of listed pages are not taken routed through proxy' => [
                'request_uri' => '/my',
                'proxy_pages' => '/',
                'should_use_proxy' => false,
            ],
            'query string is ignored' => [
                'request_uri' => '/my?widget=5',
                'proxy_pages' => '/my',
                'should_use_proxy' => true,
            ],
            'access to a page that should use proxy from list' => [
                'request_uri' => '/plugins/tracker?aid=5',
                'proxy_pages' => '/,/plugins/tracker',
                'should_use_proxy' => true,
            ],
            'access to a page that should not use proxy from list' => [
                'request_uri' => '/my',
                'proxy_pages' => '/,/plugins/tracker',
                'should_use_proxy' => false,
            ],
            'access to a page that should use proxy from list but definition has trailing slash' => [
                'request_uri' => '/plugins/tracker?aid=5',
                'proxy_pages' => '/,/plugins/tracker/',
                'should_use_proxy' => true,
            ],
        ];
    }
}
