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
use Tuleap\Test\Stubs\StoreUserPreferenceStub;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\User\NotificationOnOwnActionRetriever;
use Tuleap\Tracker\User\NotificationOnOwnActionSaver;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RemoveRecipientThatDoesntWantMailForTheirOwnActionsTest extends TestCase
{
    private PFUser $john;
    private PFUser $jane;
    private StoreUserPreferenceStub $user_preference_store;

    protected function setUp(): void
    {
        $this->user_preference_store = new StoreUserPreferenceStub();
        $this->john                  = UserTestBuilder::anActiveUser()->withUserName('john_doe')
            ->withEmail('john@example.com')
            ->withId(120)
            ->withPreferencesStore($this->user_preference_store)
            ->build();
        $this->jane                  = UserTestBuilder::anActiveUser()->withUserName('jane_biz')
            ->withEmail('jane@example.com')
            ->withId(125)
            ->withPreferencesStore($this->user_preference_store)
            ->build();

        $user_manager = $this->createStub(\UserManager::class);
        $user_manager->method('getUserById')->willReturnMap([
            [$this->john->getId(), $this->john],
            [$this->jane->getId(), $this->jane],
        ]);

        \UserManager::setInstance($user_manager);
    }

    protected function tearDown(): void
    {
        \UserManager::clearInstance();
    }

    private function removeRecipientsFrom(
        array $recipients,
        PFUser $author_of_change,
    ): array {
        $changeset = ChangesetTestBuilder::aChangeset(1234)
            ->submittedBy($author_of_change->getId())
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(345)
                    ->inTracker(
                        TrackerTestBuilder::aTracker()
                            ->withId(120)
                            ->build()
                    )->build()
            )->build();

        $strategy = new RemoveRecipientThatDoesntWantMailForTheirOwnActions(
            new NotificationOnOwnActionRetriever($this->user_preference_store)
        );
        return $strategy->removeRecipient(
            new NullLogger(),
            $changeset,
            $recipients,
            true
        );
    }

    public function testRemoveUserWhoIsAuthorOfChange(): void
    {
        $this->john->setPreference(
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NO_NOTIF
        );

        self::assertEquals(
            [],
            $this->removeRecipientsFrom([$this->john->getUserName() => Recipient::fromUser($this->john)], $this->john),
        );
    }

    public function testRemoveUserWhoIsRecipientByEmailLikeWhenUsingGlobalNotification(): void
    {
        $this->john->setPreference(
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NO_NOTIF
        );

        $recipients = [
            $this->john->getUserName() => Recipient::fromUser($this->john),
            $this->john->getEmail()    => Recipient::fromUser($this->john),

        ];
        self::assertEquals(
            [],
            $this->removeRecipientsFrom($recipients, $this->john),
        );
    }

    public function testDoNotRemoveUserWhoIsNotAuthorOfChange(): void
    {
        $this->john->setPreference(
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NO_NOTIF
        );

        $recipients = [
            $this->john->getUserName() => Recipient::fromUser($this->john),
            $this->jane->getUserName() => Recipient::fromUser($this->jane),
        ];

        self::assertEquals(
            $recipients,
            $this->removeRecipientsFrom($recipients, $this->jane),
        );
    }

    public function testDoNotRemoveUserWhoIsAuthorOfChangeWhenNoPreference(): void
    {
        $recipients = [$this->john->getUserName() => Recipient::fromUser($this->john)];
        self::assertEquals(
            $recipients,
            $this->removeRecipientsFrom($recipients, $this->john),
        );
    }
}
