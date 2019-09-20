<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\REST\GateKeeper;

abstract class GateKeeperTest extends TuleapTestCase
{
    protected $user;
    protected $anonymous;
    protected $request;
    protected $gate_keeper;

    public function setUp()
    {
        parent::setUp();
        $this->user        = new PFUser(array('user_id' => 112));
        $this->anonymous   = new PFUser(array('user_id' => 0));
        $this->gate_keeper = new GateKeeper();
        $this->request     = mock('HTTPRequest');
        $this->preserveServer('HTTP_REFERER');
    }

    public function tearDown()
    {
        unset($GLOBALS['DEBUG_MODE']);
        parent::tearDown();
    }
}

class GateKeeper_TokenAndHTTPS_Test extends GateKeeperTest
{

    public function setUp()
    {
        parent::setUp();
        $_SERVER['HTTP_REFERER'] = 'http://example.com/bla';
        stub($this->request)->getServerUrl()->returns('http://example.com');
    }

    public function itThrowsExceptionWhenTokenOrAccessKeyAuthenticationWithoutSSL()
    {
        stub($this->request)->isSecure()->returns(false);
        $this->expectException('Exception');

        $this->gate_keeper->assertAccess($this->anonymous, $this->request);
    }

    public function itLetsPassWhenTokenOrAccessKeyAuthenticationWithSSL()
    {
        stub($this->request)->isSecure()->returns(true);

        $this->gate_keeper->assertAccess($this->anonymous, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetsPassWhenTokenOrAccessKeyAuthenticationWithoutSSLButWithDebug()
    {
        stub($this->request)->isSecure()->returns(false);
        $GLOBALS['DEBUG_MODE'] = 1;

        $this->gate_keeper->assertAccess($this->anonymous, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetPassHTTPWhenCookieAuthentication()
    {
        stub($this->request)->isSecure()->returns(false);

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }
}

class GateKeeper_CSRF_Test extends GateKeeperTest
{

    public function itLetPassWhenReferMatchesHost()
    {
        $_SERVER['HTTP_REFERER'] = 'http://example.com/bla';
        stub($this->request)->getServerUrl()->returns('http://example.com');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetPassWhenReferMatchesAnEquivalentHostWithCase()
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/';
        stub($this->request)->getServerUrl()->returns('https://EXAMPLE.COM');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetPassWhenReferMatchesAnEquivalentIDNHost()
    {
        $_SERVER['HTTP_REFERER'] = 'https://チューリップ.example.com/';
        stub($this->request)->getServerUrl()->returns('https://xn--7cke4dscza1i.example.com');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetPassWhenReferMatchesAnEquivalentIDNHostWithCase()
    {
        $_SERVER['HTTP_REFERER'] = 'https://チューリップ.example.com/';
        stub($this->request)->getServerUrl()->returns('https://チューリップ.EXAMPLE.COM');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itThrowsExceptionWhenReferIsDiffentFromHost()
    {
        $_SERVER['HTTP_REFERER'] = 'http://wannabe_attacker.com/bla';
        stub($this->request)->getServerUrl()->returns('http://example.com');

        $this->expectException('Exception');
        $this->gate_keeper->assertAccess($this->user, $this->request);
    }

    public function itThrowsExceptionWhenNoReferer()
    {
        unset($_SERVER['HTTP_REFERER']);
        stub($this->request)->getServerUrl()->returns('http://example.com');

        $this->expectException('Exception');
        $this->gate_keeper->assertAccess($this->user, $this->request);
    }
}
