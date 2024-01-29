<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Notification;

use ColinODell\PsrTestLogger\TestLogger;
use Tracker;
use Tracker_Artifact_Changeset;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\TrackerFunctions\Stubs\Notification\MessageBuilderStub;
use Tuleap\TrackerFunctions\Stubs\Notification\MessageSenderStub;
use Tuleap\TrackerFunctions\Stubs\Notification\TrackerAdminRecipientsRetrieverStub;

final class SendNotificationToTrackerAdministratorTest extends TestCase
{
    private TestLogger $logger;
    private Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $tracker      = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->method('getId')->willReturn(2);
        $artifact        = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset('1')->build())
            ->build();
        $this->changeset = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();
    }

    public function testItFaultWhenNoRecipient(): void
    {
        $notification = new SendNotificationToTrackerAdministrator(
            TrackerAdminRecipientsRetrieverStub::buildWithErrResult('No tracker administrator found'),
            MessageBuilderStub::buildWithOkResult(),
            MessageSenderStub::buildWithOkResult(),
            $this->logger,
        );

        $notification->sendNotificationToTrackerAdministrator($this->changeset);
        self::assertTrue($this->logger->hasWarning('No tracker administrator found'));
    }

    public function testItFaultWhenMessageBuilderFault(): void
    {
        $notification = new SendNotificationToTrackerAdministrator(
            TrackerAdminRecipientsRetrieverStub::buildWithOkResult(UserTestBuilder::anActiveUser()->build()),
            MessageBuilderStub::buildWithErrResult('Error message'),
            MessageSenderStub::buildWithOkResult(),
            $this->logger,
        );

        $notification->sendNotificationToTrackerAdministrator($this->changeset);
        self::assertTrue($this->logger->hasWarning('Error message'));
    }

    public function testItFaultWhenMessageSenderFault(): void
    {
        $notification = new SendNotificationToTrackerAdministrator(
            TrackerAdminRecipientsRetrieverStub::buildWithOkResult(UserTestBuilder::anActiveUser()->build()),
            MessageBuilderStub::buildWithOkResult(),
            MessageSenderStub::buildWithErrResult('Another error message'),
            $this->logger,
        );

        $notification->sendNotificationToTrackerAdministrator($this->changeset);
        self::assertTrue($this->logger->hasWarning('Another error message'));
    }

    public function testItLogNothingWhenAllGoesWell(): void
    {
        $notification = new SendNotificationToTrackerAdministrator(
            TrackerAdminRecipientsRetrieverStub::buildWithOkResult(UserTestBuilder::anActiveUser()->build()),
            MessageBuilderStub::buildWithOkResult(),
            MessageSenderStub::buildWithOkResult(),
            $this->logger,
        );

        $notification->sendNotificationToTrackerAdministrator($this->changeset);
        self::assertFalse($this->logger->hasWarningRecords());
    }
}
