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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Webhook\ArtifactPayload;
use Tuleap\Tracker\Webhook\Webhook;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Webhook\Emitter;

require_once __DIR__ . '/../../../../bootstrap.php';

class WebhookNotificationTaskTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testConfiguredWebhooksAreSent()
    {
        $logger  = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $emitter = \Mockery::mock(Emitter::class);
        $factory = \Mockery::mock(WebhookFactory::class);

        $logger->shouldReceive('debug')->atLeast(1);

        $tracker   = \Mockery::mock(\Tracker::class);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getId')->andReturns(1);
        $changeset->shouldReceive('getTracker')->andReturns($tracker);

        $webhook_1 = \Mockery::mock(Webhook::class);
        $webhook_2 = \Mockery::mock(Webhook::class);
        $factory->shouldReceive('getWebhooksForTracker')->andReturns([$webhook_1, $webhook_2]);

        $webhook_notification_task = new WebhookNotificationTask($logger, $emitter, $factory);

        $emitter->shouldReceive('emit')
            ->with(\Mockery::type(ArtifactPayload::class), $webhook_1, $webhook_2)->once();

        $webhook_notification_task->execute($changeset);
    }
}
