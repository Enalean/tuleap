<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Settings;

use PFUser;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\StoreUserPreferenceStub;
use Tuleap\Tracker\User\NotificationOnAllUpdatesRetriever;
use Tuleap\Tracker\User\NotificationOnAllUpdatesSaver;
use Tuleap\Tracker\User\NotificationOnOwnActionRetriever;
use Tuleap\Tracker\User\NotificationOnOwnActionSaver;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NoGlobalNotificationLabelBuilderTest extends TestCase
{
    private PFUser $current_user;

    #[\Override]
    protected function setUp(): void
    {
        $this->current_user = UserTestBuilder::anActiveUser()->build();
    }

    private function getInputLabel(StoreUserPreferenceStub $pref): string
    {
        $no_global_notification_label_builder = new NoGlobalNotificationLabelBuilder(
            UserGlobalAccountNotificationSettings::build(
                ProvideCurrentUserStub::buildWithUser(
                    $this->current_user
                ),
                new NotificationOnAllUpdatesRetriever($pref),
                new NotificationOnOwnActionRetriever($pref)
            )
        );
        return $no_global_notification_label_builder->getInputLabel();
    }

    public function testNotifyOnArtifactUpdateLabel(): void
    {
        $store_preference = new StoreUserPreferenceStub();
        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnAllUpdatesSaver::PREFERENCE_NAME,
            NotificationOnAllUpdatesSaver::VALUE_NOTIF
        );
        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NOTIF
        );

        self::assertStringContainsString(
            'Notify me on all update of artifacts I touch',
            $this->getInputLabel($store_preference)
        );
    }

    public function testNotifyWhenOnlyMeUpdateAnArtifactLabel(): void
    {
        $store_preference = new StoreUserPreferenceStub();
        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnAllUpdatesSaver::PREFERENCE_NAME,
            NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF
        );
        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NOTIF
        );

        self::assertStringContainsString(
            'Notify me on all updates I do on artifacts',
            $this->getInputLabel($store_preference)
        );
    }

    public function testNotifyWhenOtherUserUpdateArtifactITouchedLabel(): void
    {
        $store_preference = new StoreUserPreferenceStub();
        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnAllUpdatesSaver::PREFERENCE_NAME,
            NotificationOnAllUpdatesSaver::VALUE_NOTIF
        );

        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NO_NOTIF
        );

        self::assertStringContainsString(
            'Notify me when other users change an artifact I touched',
            $this->getInputLabel($store_preference)
        );
    }

    public function testNotifyWhenOtherUserUpdateArtifactICreatedOrIMAssignedToLabel(): void
    {
        $store_preference = new StoreUserPreferenceStub();
        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnAllUpdatesSaver::PREFERENCE_NAME,
            NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF
        );

        $store_preference->set(
            (int) $this->current_user->getId(),
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NO_NOTIF
        );

        self::assertStringContainsString(
            "Notify me when other users change artifacts I created or I'm assigned to",
            $this->getInputLabel($store_preference)
        );
    }
}
