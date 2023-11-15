<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Semantic_Title;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;

final class EmailNotificationAttachmentProviderTest extends TestCase
{
    private readonly Tracker_Artifact_Changeset $changeset;
    private readonly PFUser $recipient;
    private readonly TestLogger $logger;
    private Tracker_Semantic_Title|MockObject $semantic_title;

    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset("1001")->build();
        $this->recipient = UserTestBuilder::buildWithDefaults();
        $this->logger    = new TestLogger();

        $this->semantic_title = $this->createMock(Tracker_Semantic_Title::class);
        Tracker_Semantic_Title::setInstance($this->semantic_title, $this->changeset->getTracker());
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Title::clearInstances();
    }

    public function testNoAttachentsWhenTrackerIsNotConfiguredTo(): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger);

        self::assertEmpty($attachements);
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testNoAttachentsWhenTrackerDoesNotHaveTitleSemantic(): void
    {
        $provider = new EmailNotificationAttachmentProvider(
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
        );

        $this->semantic_title->method('getField')->willReturn(null);

        $attachements = $provider->getAttachments($this->changeset, $this->recipient, $this->logger);

        self::assertEmpty($attachements);
        self::assertTrue($this->logger->hasDebugThatContains('Tracker is configured to send calendar events alongside notification'));
        self::assertTrue($this->logger->hasDebugThatContains('The tracker does not have title semantic, we cannot build calendar events'));
    }
}
