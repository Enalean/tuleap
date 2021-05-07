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

final class URLVerificationPermissionsOverriderRegularPlatformTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private $url_verification;
    private $server;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \Mockery::mock(\PFUser::class);

        $event_manager = \Mockery::spy(\EventManager::class);

        $this->url_verification = \Mockery::mock(\URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->url_verification->shouldReceive('getEventManager')->andReturns($event_manager);
        $this->url_verification->shouldReceive('getCurrentUser')->andReturns($this->user);
        $fixtures = dirname(__FILE__) . '/_fixtures';
        $GLOBALS['Language']->method('getContent')->willReturn($fixtures . '/empty.txt');

        $this->server = ['SERVER_NAME' => 'example.com'];

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
    }

    protected function getScriptChunk(): ?string
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
