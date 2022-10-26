<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\RemoveRecipient;

use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\User\NotificationOnOwnActionPreference;

final class RemoveRecipientThatDoesntWantMailForThereOwnActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $user_manager = $this->createStub(\UserManager::class);
        $user_manager->method('getUserById')->willReturnMap([
            [120, UserTestBuilder::anActiveUser()->withUserName('john_doe')->withId(120)->build()],
            [125, UserTestBuilder::anActiveUser()->withUserName('jane_biz')->withId(125)->build()],
        ]);

        \UserManager::setInstance($user_manager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \UserManager::clearInstance();
    }

    public function testRemoveUserWhoIsAuthorOfChange(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('john_doe')->withId(120)->build();
        NotificationOnOwnActionPreference::updateUserDoesNotWantNotification($user);

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset('1234')
            ->submittedBy($user->getId())
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(345)
                    ->inTracker(
                        TrackerTestBuilder::aTracker()
                            ->withId(120)
                            ->build()
                    )
                    ->build()
            )
            ->build();

        self::assertEquals(
            [],
            $strategy->removeRecipient(new NullLogger(), $changeset, [$user->getUserName() => Recipient::fromUser($user)], true),
        );
    }

    public function testDoNotRemoveUserWhoIsNotAuthorOfChange(): void
    {
        $subscriber = UserTestBuilder::anActiveUser()->withUserName('john_doe')->withId(120)->build();
        NotificationOnOwnActionPreference::updateUserDoesNotWantNotification($subscriber);

        $change_author = UserTestBuilder::anActiveUser()->withUserName('jane_biz')->withId(125)->build();

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset('1234')
            ->submittedBy($change_author->getId())
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(345)
                    ->inTracker(
                        TrackerTestBuilder::aTracker()
                            ->withId(120)
                            ->build()
                    )
                    ->build()
            )
            ->build();

        $recipients = [
            $subscriber->getUserName() => Recipient::fromUser($subscriber),
            $change_author->getUserName() => Recipient::fromUser($change_author),
        ];

        self::assertEquals(
            $recipients,
            $strategy->removeRecipient(new NullLogger(), $changeset, $recipients, true),
        );
    }

    public function testDoNotRemoveUserWhoIsAuthorOfChangeWhenNoPreference(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('john_doe')->withId(120)->build();

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset('1234')
            ->submittedBy($user->getId())
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(345)
                    ->inTracker(
                        TrackerTestBuilder::aTracker()
                            ->withId(120)
                            ->build()
                    )
                    ->build()
            )
            ->build();

        $recipients = [$user->getUserName() => Recipient::fromUser($user)];
        self::assertEquals(
            $recipients,
            $strategy->removeRecipient(new NullLogger(), $changeset, $recipients, true),
        );
    }
}
