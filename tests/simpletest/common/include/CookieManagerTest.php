<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class CookieManagerTest extends TuleapTestCase {
    /** @var CookieManager */
    private $cookie_manager;

    public function setUp() {
        parent::setUp();
        $this->cookie_manager = partial_mock('CookieManager', array('phpsetcookie'));
    }

    public function tearDown() {
        unset($GLOBALS['sys_cookie_domain']);
        unset($GLOBALS['sys_default_domain']);
        parent::tearDown();
    }

    public function itSetsTheCookieDomainWhenADomainName() {
        $GLOBALS['sys_cookie_domain'] = 'example.com';
        stub($this->cookie_manager)->phpsetcookie('*', '*', '*', '*', '.example.com', '*')->once();
        $this->cookie_manager->setCookie('bla', 'bla', 'bla');
    }

    public function itSetsTheCookieDomainWhenADomainNameWithoutTLD() {
        $GLOBALS['sys_cookie_domain'] = 'gg32';
        stub($this->cookie_manager)->phpsetcookie('*', '*', '*', '*', '', '*')->once();
        $this->cookie_manager->setCookie('bla', 'bla', 'bla');
    }

    public function itSetTheCookieDomainWhenIPAdress() {
        $GLOBALS['sys_cookie_domain'] = '127.0.0.1';
        stub($this->cookie_manager)->phpsetcookie('*', '*', '*', '*', '', '*')->once();
        $this->cookie_manager->setCookie('bla', 'bla', 'bla');
    }

    public function itSetsTheCookieDomainWhenADomainNameAndPort() {
        $GLOBALS['sys_cookie_domain'] = 'example.com:8080';
        stub($this->cookie_manager)->phpsetcookie('*', '*', '*', '*', '.example.com', '*')->once();
        $this->cookie_manager->setCookie('bla', 'bla', 'bla');
    }
}
