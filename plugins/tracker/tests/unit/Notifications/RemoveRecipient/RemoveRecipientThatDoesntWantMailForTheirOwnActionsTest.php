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

use PFUser;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\User\NotificationOnOwnActionPreference;

final class RemoveRecipientThatDoesntWantMailForTheirOwnActionsTest extends TestCase
{
    private PFUser $john;
    private PFUser $jane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->john = UserTestBuilder::anActiveUser()->withUserName('john_doe')->withEmail('john@example.com')->withId(120)->build();
        $this->jane = UserTestBuilder::anActiveUser()->withUserName('jane_biz')->withEmail('jane@example.com')->withId(125)->build();

        $user_manager = $this->createStub(\UserManager::class);
        $user_manager->method('getUserById')->willReturnMap([
            [$this->john->getId(), $this->john],
            [$this->jane->getId(), $this->jane],
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
        NotificationOnOwnActionPreference::updateUserDoesNotWantNotification($this->john);

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset(1234)
            ->submittedBy($this->john->getId())
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
            $strategy->removeRecipient(new NullLogger(), $changeset, [$this->john->getUserName() => Recipient::fromUser($this->john)], true),
        );
    }

    public function testRemoveUserWhoIsRecepientByEmailLikeWhenUsingGlobalNotification(): void
    {
        NotificationOnOwnActionPreference::updateUserDoesNotWantNotification($this->john);

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset(1234)
            ->submittedBy($this->john->getId())
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
            $this->john->getUserName() => Recipient::fromUser($this->john),
            $this->john->getEmail()    => Recipient::fromUser($this->john),

        ];
        self::assertEquals(
            [],
            $strategy->removeRecipient(new NullLogger(), $changeset, $recipients, true),
        );
    }

    public function testDoNotRemoveUserWhoIsNotAuthorOfChange(): void
    {
        NotificationOnOwnActionPreference::updateUserDoesNotWantNotification($this->john);

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset(1234)
            ->submittedBy($this->jane->getId())
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
            $this->john->getUserName() => Recipient::fromUser($this->john),
            $this->jane->getUserName() => Recipient::fromUser($this->jane),
        ];

        self::assertEquals(
            $recipients,
            $strategy->removeRecipient(new NullLogger(), $changeset, $recipients, true),
        );
    }

    public function testDoNotRemoveUserWhoIsAuthorOfChangeWhenNoPreference(): void
    {
        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions();

        $changeset = ChangesetTestBuilder::aChangeset(1234)
            ->submittedBy($this->john->getId())
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

        $recipients = [$this->john->getUserName() => Recipient::fromUser($this->john)];
        self::assertEquals(
            $recipients,
            $strategy->removeRecipient(new NullLogger(), $changeset, $recipients, true),
        );
    }
}
