<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Enalean\LicenseManager;

use Tuleap\Enalean\LicenseManager\Webhook\UserCounterPayload;
use Tuleap\Webhook\Emitter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusActivityEmitterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testWebhookIsCalledWhenAvailable(): void
    {
        $emitter = $this->createMock(Emitter::class);
        $emitter->expects($this->once())->method('emit');

        $status_activity_emitter = new StatusActivityEmitter($emitter);

        $payload = $this->createStub(UserCounterPayload::class);
        $payload->method('getPayload')->willReturn(['users' => [], 'max_users' => 0]);

        $status_activity_emitter->emit($payload, 'https://example.com');
    }

    public function testWebhookDoesNotTryToEmitWhenNoURLIsProvided(): void
    {
        $emitter = $this->createMock(Emitter::class);
        $emitter->expects($this->never())->method('emit');

        $status_activity_emitter = new StatusActivityEmitter($emitter);

        $payload = $this->createStub(UserCounterPayload::class);
        $payload->method('getPayload')->willReturn(['users' => [], 'max_users' => 0]);

        $status_activity_emitter->emit($payload, '');
    }
}
