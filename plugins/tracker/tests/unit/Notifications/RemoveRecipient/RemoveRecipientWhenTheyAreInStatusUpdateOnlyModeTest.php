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

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChange;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RemoveRecipientWhenTheyAreInStatusUpdateOnlyModeTest extends TestCase
{
    #[DataProvider('getTestData')]
    public function testRemoval(array $expected, bool $has_subscribed, bool $has_changed): void
    {
        $strategy = new RemoveRecipientWhenTheyAreInStatusUpdateOnlyMode(
            new class ($has_subscribed) implements UserNotificationOnlyStatusChange {
                public function __construct(private bool $has_subscribed)
                {
                }

                #[\Override]
                public function doesUserIdHaveSubscribeOnlyForStatusChangeNotification(
                    int $user_id,
                    int $tracker_id,
                ): bool {
                    return $this->has_subscribed;
                }
            },
            new class ($has_changed) implements ArtifactStatusChangeDetector {
                public function __construct(private bool $has_changed)
                {
                }

                #[\Override]
                public function hasChanged(Tracker_Artifact_Changeset $changeset): bool
                {
                    return $this->has_changed;
                }
            }
        );

        $changeset = ChangesetTestBuilder::aChangeset(1234)
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
            $expected,
            $strategy->removeRecipient(new NullLogger(), $changeset, ['john_doe' => Recipient::fromUser(UserTestBuilder::buildWithDefaults())], true),
        );
    }

    public static function getTestData(): iterable
    {
        return [
            'status has changed and user subscribed to get updates, user kept' => [
                'expected' => ['john_doe' => Recipient::fromUser(UserTestBuilder::buildWithDefaults())],
                'has_subscribed' => true,
                'has_changed' => true,
            ],
            'status did not change and user subscribed to get updates, user removed' => [
                'expected' => [],
                'has_subscribed' => true,
                'has_changed' => false,
            ],
            'status did not change and user did not subscribed to get updates, user kept' => [
                'expected' => ['john_doe' => Recipient::fromUser(UserTestBuilder::buildWithDefaults())],
                'has_subscribed' => false,
                'has_changed' => false,
            ],
        ];
    }
}
