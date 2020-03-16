<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap;

use ForgeAccess;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsOverrider_PermissionsOverriderManager;
use PHPUnit\Framework\TestCase;

final class URLVerificationPermissionsOverriderRestrictedPlatformAndNoOverriderTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock, ForgeConfigSandbox;

    private $url_verification;
    private $event_manager;
    private $overrider_manager;
    private $server;
    private $user;
    /**
     * @var string
     */
    private $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \Mockery::mock(\PFUser::class);

        $this->event_manager     = \Mockery::spy(\EventManager::class);
        $this->overrider_manager = \Mockery::spy(\PermissionsOverrider_PermissionsOverriderManager::class);

        $this->url_verification = \Mockery::mock(\URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->url_verification->shouldReceive('getEventManager')->andReturns($this->event_manager);
        $this->url_verification->shouldReceive('getCurrentUser')->andReturns($this->user);
        PermissionsOverrider_PermissionsOverriderManager::setInstance($this->overrider_manager);
        $this->fixtures = dirname(__FILE__) . '/_fixtures';
        $GLOBALS['Language']->shouldReceive('getContent')->andReturns($this->fixtures . '/empty.txt');

        $this->server = array('SERVER_NAME' => 'example.com');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->overrider_manager->shouldReceive('doesOverriderAllowUserToAccessPlatform')->andReturns(false);
    }

    protected function tearDown(): void
    {
        PermissionsOverrider_PermissionsOverriderManager::clearInstance();
        parent::tearDown();
    }

    private function getScriptChunk(): ?string
    {
        $this->url_verification->verifyRequest($this->server);
        $chunks = $this->url_verification->getUrlChunks();
        return $chunks['script'] ?? null;
    }

    public function testItForceAnonymousToLoginToAccessRoot(): void
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/';

        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $this->assertEquals('/account/login.php?return_to=%2Fmy%2F', $this->getScriptChunk());
    }

    public function testItForceAnonymousToLoginToAccessScript(): void
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script/';

        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $this->assertEquals('/account/login.php?return_to=%2Fscript%2F', $this->getScriptChunk());
    }

    public function testItForceAnonymousToLoginToAccessScriptInLightView(): void
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script?pv=2';

        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $this->assertEquals('/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2', $this->getScriptChunk());
    }
}
