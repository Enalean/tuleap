<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Enalean\LicenseManager;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Enalean\LicenseManager\Webhook\UserCounterPayload;
use Tuleap\Webhook\Emitter;

class StatusActivityEmitterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWebhookIsCalledWhenAvailable()
    {
        $emitter = \Mockery::mock(Emitter::class);
        $emitter->shouldReceive('emit')->once();

        $status_activity_emitter = new StatusActivityEmitter($emitter);

        $payload = \Mockery::mock(UserCounterPayload::class);
        $payload->shouldReceive('getPayload')->andReturns(['users' => [], 'max_users' => 0]);

        $status_activity_emitter->emit($payload, 'https://example.com');
    }

    public function testWebhookDoesNotTryToEmitWhenNoURLIsProvided()
    {
        $emitter = \Mockery::mock(Emitter::class);

        $status_activity_emitter = new StatusActivityEmitter($emitter);

        $payload = \Mockery::mock(UserCounterPayload::class);
        $payload->shouldReceive('getPayload')->andReturns(['users' => [], 'max_users' => 0]);

        $status_activity_emitter->emit($payload, '');
    }
}