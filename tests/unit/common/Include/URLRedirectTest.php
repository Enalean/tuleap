<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class URLRedirectTest extends \PHPUnit\Framework\TestCase //phpcs:ignore
{
    use \Tuleap\ForgeConfigSandbox;

    private $url_redirect;

    public function setUp(): void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->url_redirect = new URLRedirect($this->createMock(\EventManager::class));
    }

    public function testItCreatesALoginURLReturningToTheCurrentPage(): void
    {
        $login_url = $this->url_redirect->buildReturnToLogin(['REQUEST_URI' => '/some_tuleap_page']);
        self::assertEquals('/account/login.php?return_to=%2Fsome_tuleap_page', $login_url);
    }

    public function testItCreatesALoginURLToTheUserProfileIfHomepageOrLoginOrRegisterPage(): void
    {
        $login_url_from_homepage = $this->url_redirect->buildReturnToLogin(['REQUEST_URI' => '/']);
        self::assertEquals('/account/login.php?return_to=%2Fmy%2F', $login_url_from_homepage);

        $login_url_from_login_page = $this->url_redirect->buildReturnToLogin(
            ['REQUEST_URI' => '/account/login.php?return_to=some_page']
        );
        self::assertEquals('/account/login.php?return_to=%2Fmy%2F', $login_url_from_login_page);

        $login_url_from_register_page = $this->url_redirect->buildReturnToLogin(
            ['REQUEST_URI' => '/account/register.php']
        );
        self::assertEquals('/account/login.php?return_to=%2Fmy%2F', $login_url_from_register_page);
    }

    public function testItNotRedirectToUntrustedWebsite(): void
    {
        self::assertEquals(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl('/my/redirect.php', 'http://evil.example.com/')
        );
        self::assertEquals(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl('/my/redirect.php', 'https://evil.example.com/')
        );
    }

    public function testItNotRedirectToUntrustedCode(): void
    {
        self::assertEquals(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl('/my/redirect.php', 'javascript:alert(1)')
        );
        self::assertEquals(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl('/my/redirect.php', 'vbscript:msgbox(1)')
        );
    }
}
