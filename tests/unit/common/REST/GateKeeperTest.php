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

use PFUser;
use Tuleap\ForgeConfigSandbox;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GateKeeperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var PFUser
     */
    private $anonymous;
    /**
     * @var GateKeeper
     */
    private $gate_keeper;

    #[\Override]
    protected function setUp(): void
    {
        $this->user        = new PFUser(['user_id' => 112, 'language_id' => 'en']);
        $this->anonymous   = new PFUser(['user_id' => 0, 'language_id' => 'en']);
        $this->gate_keeper = new GateKeeper();
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['HTTP_SEC_FETCH_MODE']);
    }

    public function testItLetsPassWhenTokenOrAccessKeyAuthentication(): void
    {
        $this->gate_keeper->assertAccess($this->anonymous);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testRequestPassesWhenComesFromTheSameOriginInAExpectedWay(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
        $_SERVER['HTTP_SEC_FETCH_MODE'] = 'cors';

        $this->expectNotToPerformAssertions();
        $this->gate_keeper->assertAccess($this->user);
    }

    public function testItLetPassWhenReferMatchesHost(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/bla';
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $this->gate_keeper->assertAccess($this->user);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesAnEquivalentHostWithCase(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/';
        \ForgeConfig::set('sys_default_domain', 'EXAMPLE.COM');

        $this->gate_keeper->assertAccess($this->user);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesAnEquivalentIDNHost(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://チューリップ.example.com/';
        \ForgeConfig::set('sys_default_domain', 'xn--7cke4dscza1i.example.com');

        $this->gate_keeper->assertAccess($this->user);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItLetPassWhenReferMatchesAnEquivalentIDNHostWithCase(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://チューリップ.example.com/';
        \ForgeConfig::set('sys_default_domain', 'チューリップ.EXAMPLE.COM');

        $this->gate_keeper->assertAccess($this->user);
        $this->assertTrue(true, 'No exception should be raised');
    }

    public function testItThrowsExceptionWhenReferIsDifferentFromHost(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://wannabe_attacker.example.org/bla';
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $this->expectException(\Exception::class);
        $this->gate_keeper->assertAccess($this->user);
    }

    public function testItThrowsExceptionWhenNoReferer(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $this->expectException(\Exception::class);
        $this->gate_keeper->assertAccess($this->user);
    }
}
