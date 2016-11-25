<?php

/**
 * Copyright (c) Enalean, 2014-2016. All Rights Reserved.
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
class URLRedirect_MakeUrlTest extends TuleapTestCase {

    private $request;
    private $url_redirect;

    public function setUp() {
        $event_manager      = mock('EventManager');
        $this->url_redirect = new URLRedirect($event_manager);
        $this->request = mock('HTTPRequest');
        $GLOBALS['sys_force_ssl'] = 1;
        $GLOBALS['sys_https_host'] = 'example.com';
        $GLOBALS['sys_default_domain'] = 'example.com';
        parent::setUp();
    }

    public function tearDown() {
        unset($GLOBALS['sys_force_ssl']);
        unset($GLOBALS['sys_https_host']);
        unset($GLOBALS['sys_default_domain']);
        parent::tearDown();
    }

    public function itStayInSSLWhenForceSSLIsOn() {
        stub($this->request)->isSecure()->returns(true);
        $GLOBALS['sys_force_ssl'] = 1;

        $this->assertEqual(
            '/my/index.php',
            $this->url_redirect->makeReturnToUrl($this->request, '/my/index.php', '')
        );
    }

    public function itRedirectToHttpWhenForceSSLIsOffAndNoStayInSSL() {
        stub($this->request)->isSecure()->returns(true);
        $GLOBALS['sys_force_ssl'] = 0;
        stub($this->request)->existAndNonEmpty('stay_in_ssl')->returns(true);
        stub($this->request)->get('stay_in_ssl')->returns(0);

        $this->assertEqual(
            'http://example.com/my/index.php',
            $this->url_redirect->makeReturnToUrl($this->request, '/my/index.php', '')
        );
    }

    public function itRedirectToHttpWhenForceSSLIsOffAndNoStayInSSL2() {
        stub($this->request)->isSecure()->returns(true);
        $GLOBALS['sys_force_ssl'] = 0;
        stub($this->request)->existAndNonEmpty('stay_in_ssl')->returns(false);
        stub($this->request)->get('stay_in_ssl')->returns(false);

        $this->assertEqual('http://example.com/my/index.php', $this->url_redirect->makeReturnToUrl($this->request, '/my/index.php', ''));
    }

    public function itStayInSSLWhenForceSSLIsOffAndNoStayInSSL() {
        stub($this->request)->isSecure()->returns(true);
        $GLOBALS['sys_force_ssl'] = 0;
        stub($this->request)->existAndNonEmpty('stay_in_ssl')->returns(true);
        stub($this->request)->get('stay_in_ssl')->returns(1);

        $this->assertEqual('/my/index.php', $this->url_redirect->makeReturnToUrl($this->request, '/my/index.php', ''));
    }

    public function itStayUnencryptedWhenForceSSLIsOffAndNoStayInSSL() {
        stub($this->request)->isSecure()->returns(false);
        $GLOBALS['sys_force_ssl'] = 0;
        stub($this->request)->existAndNonEmpty('stay_in_ssl')->returns(true);
        stub($this->request)->get('stay_in_ssl')->returns(0);

        $this->assertEqual('/my/index.php', $this->url_redirect->makeReturnToUrl($this->request, '/my/index.php', ''));
    }

    public function itNotRedirectToUntrustedWebsite() {
        $this->assertEqual(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl($this->request, '/my/redirect.php', 'http://evil.example.com/')
        );
        $this->assertEqual(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl($this->request, '/my/redirect.php', 'https://evil.example.com/')
        );
    }

    public function itNotRedirectToUntrustedCode() {
        $this->assertEqual(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl($this->request, '/my/redirect.php', 'javascript:alert(1)')
        );
        $this->assertEqual(
            '/my/redirect.php?return_to=/',
            $this->url_redirect->makeReturnToUrl($this->request, '/my/redirect.php', 'vbscript:msgbox(1)')
        );
    }

}
