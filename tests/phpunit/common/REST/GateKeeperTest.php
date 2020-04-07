<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\REST;

use HTTPRequest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

final class GateKeeperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var PFUser
     */
    private $anonymous;
    /**
     * @var HTTPRequest|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $request;
    /**
     * @var GateKeeper
     */
    private $gate_keeper;

    protected function setUp(): void
    {
        $this->user        = new PFUser(array('user_id' => 112, 'language_id' => 'en'));
        $this->anonymous   = new PFUser(array('user_id' => 0, 'language_id' => 'en'));
        $this->gate_keeper = new GateKeeper();
        $this->request     = \Mockery::spy(HTTPRequest::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['DEBUG_MODE'], $_SERVER['HTTP_REFERER']);
    }

    public function testItThrowsExceptionWhenTokenOrAccessKeyAuthenticationWithoutSSL(): void
    {
        $this->request->shouldReceive('isSecure')->andReturns(false);
        $this->expectException(\Exception::class);

        $this->gate_keeper->assertAccess($this->anonymous, $this->request);
    }

    public function testItLetsPassWhenTokenOrAccessKeyAuthenticationWithSSL(): void
    {
        $this->request->shouldReceive('isSecure')->andReturns(true);

        $this->gate_keeper->assertAccess($this->anonymous, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetsPassWhenTokenOrAccessKeyAuthenticationWithoutSSLButWithDebug(): void
    {
        $this->request->shouldReceive('isSecure')->andReturns(false);
        $GLOBALS['DEBUG_MODE'] = 1;

        $this->gate_keeper->assertAccess($this->anonymous, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassHTTPWhenCookieAuthentication(): void
    {
        $this->request->shouldReceive('isSecure')->andReturns(false);

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesHost(): void
    {
        $_SERVER['HTTP_REFERER'] = 'http://example.com/bla';
        $this->request->shouldReceive('getServerUrl')->andReturns('http://example.com');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesAnEquivalentHostWithCase(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/';
        $this->request->shouldReceive('getServerUrl')->andReturns('https://EXAMPLE.COM');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesAnEquivalentIDNHost(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://チューリップ.example.com/';
        $this->request->shouldReceive('getServerUrl')->andReturns('https://xn--7cke4dscza1i.example.com');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesAnEquivalentIDNHostWithCase(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://チューリップ.example.com/';
        $this->request->shouldReceive('getServerUrl')->andReturns('https://チューリップ.EXAMPLE.COM');

        $this->gate_keeper->assertAccess($this->user, $this->request);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItThrowsExceptionWhenReferIsDiffentFromHost(): void
    {
        $_SERVER['HTTP_REFERER'] = 'http://wannabe_attacker.com/bla';
        $this->request->shouldReceive('getServerUrl')->andReturns('http://example.com');

        $this->expectException(\Exception::class);
        $this->gate_keeper->assertAccess($this->user, $this->request);
    }

    public function testItThrowsExceptionWhenNoReferer(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        $this->request->shouldReceive('getServerUrl')->andReturns('http://example.com');

        $this->expectException(\Exception::class);
        $this->gate_keeper->assertAccess($this->user, $this->request);
    }
}
