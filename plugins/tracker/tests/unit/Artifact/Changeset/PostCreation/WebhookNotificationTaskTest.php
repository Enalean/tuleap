<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Webhook\ArtifactPayloadBuilderStub;
use Tuleap\Tracker\Webhook\ArtifactPayload;
use Tuleap\Tracker\Webhook\Webhook;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Webhook\Emitter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebhookNotificationTaskTest extends TestCase
{
    public function testConfiguredWebhooksAreSent(): void
    {
        $logger  = new TestLogger();
        $emitter = $this->createMock(Emitter::class);
        $factory = $this->createMock(WebhookFactory::class);

        $tracker   = TrackerTestBuilder::aTracker()->build();
        $changeset = ChangesetTestBuilder::aChangeset(1)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build())
            ->build();

        $webhook_1 = $this->createMock(Webhook::class);
        $webhook_2 = $this->createMock(Webhook::class);
        $factory->method('getWebhooksForTracker')->willReturn([$webhook_1, $webhook_2]);

        $builder = ArtifactPayloadBuilderStub::withEmptyPayload();

        $webhook_notification_task = new WebhookNotificationTask($logger, $emitter, $factory, $builder);

        $emitter->expects(self::once())->method('emit')
            ->with(self::isInstanceOf(ArtifactPayload::class), $webhook_1, $webhook_2);

        $webhook_notification_task->execute($changeset, new PostCreationTaskConfiguration(true, []));

        self::assertTrue($logger->hasDebugRecords());
    }
}
