<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

abstract class GateKeeperTestPHP53  extends TuleapTestCase {
    protected $user;
    protected $anonymous;

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();
        $this->user        = new PFUser(array('user_id' => 112));
        $this->anonymous   = new PFUser(array('user_id' => 0));
        $this->gate_keeper = new GateKeeper();
        $this->preserveServer('HTTPS');
        $this->preserveServer('HTTP_REFERER');
        $this->preserveServer('HTTP_HOST');
    }

    public function tearDown() {
        unset($GLOBALS['DEBUG_MODE']);
        parent::tearDown();
    }
}

class GateKeeper_TokenAndHTTPS_TestPHP53  extends GateKeeperTestPHP53 {

    public function setUp() {
        parent::setUp();
        $_SERVER['HTTP_REFERER']      = 'http://example.com/bla';
        $_SERVER['HTTP_HOST']         = 'example.com';
    }

    public function itThrowsExceptionWhenTokenAuthenticationWithoutSSL() {
        unset($_SERVER['HTTPS']);
        $this->expectException('Exception');

        $this->gate_keeper->assertAccess($this->anonymous);
    }

    public function itLetsPassWhenTokenAuthenticationWithSSL() {
        $_SERVER['HTTPS'] = 1;

        $this->gate_keeper->assertAccess($this->anonymous);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetsPassWhenTokenAuthenticationWithoutSSLButWithDebug() {
        unset($_SERVER['HTTPS']);
        $GLOBALS['DEBUG_MODE'] = 1;

        $this->gate_keeper->assertAccess($this->anonymous);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itLetPassHTTPWhenCookieAuthentication() {
        unset($_SERVER['HTTPS']);

        $this->gate_keeper->assertAccess($this->user);
        $this->assertTrue(true, 'No exception should be raised');
    }
}

class GateKeeper_CSRF_TestPHP53 extends GateKeeperTestPHP53 {

    public function itLetPassWhenReferMatchesHost() {
        $_SERVER['HTTP_REFERER'] = 'http://example.com/bla';
        $_SERVER['HTTP_HOST']    = 'example.com';

        $this->gate_keeper->assertAccess($this->user);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function itThrowsExceptionWhenReferIsDiffentFromHost() {
        $_SERVER['HTTP_REFERER'] = 'http://wannabe_attacker.com/bla';
        $_SERVER['HTTP_HOST']    = 'example.com';

        $this->expectException('Exception');
        $this->gate_keeper->assertAccess($this->user);
    }

    public function itThrowsExceptionWhenNoReferer() {
        unset($_SERVER['HTTP_REFERER']);
        $_SERVER['HTTP_HOST']    = 'example.com';

        $this->expectException('Exception');
        $this->gate_keeper->assertAccess($this->user);
    }
}