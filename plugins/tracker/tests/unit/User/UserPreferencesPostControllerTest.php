<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\User;

use Codendi_Mail_Interface;
use Feedback;
use HTTPRequest;
use PFUser;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\StoreUserPreferenceStub;
use Tuleap\User\Account\DisplayNotificationsController;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserPreferencesPostControllerTest extends TestCase
{
    private StoreUserPreferenceStub $preferences_store;

    #[\Override]
    protected function setUp(): void
    {
        $this->preferences_store = new StoreUserPreferenceStub();
    }

    private function processRequest(HTTPRequest $request, LayoutInspector $inspector, PFUser $user): void
    {
        $controller = new UserPreferencesPostController(
            ProvideAndRetrieveUserStub::build($user),
            CSRFSynchronizerTokenStub::buildSelf(),
            new NotificationOnOwnActionSaver(
                new NotificationOnOwnActionRetriever($this->preferences_store),
                $this->preferences_store,
            ),
            new NotificationOnAllUpdatesSaver(
                new NotificationOnAllUpdatesRetriever($this->preferences_store),
                $this->preferences_store,
            )
        );
        $controller->process(
            $request,
            new TestLayout($inspector),
            [],
        );
    }

    public function testItRedirectAnonymousUser(): void
    {
        $this->expectException(NotFoundException::class);
        $this->processRequest(
            HTTPRequestBuilder::get()->withParam('email_format', 'html')->build(),
            new LayoutInspector(),
            UserTestBuilder::anAnonymousUser()->build(),
        );
    }

    public function testItAppliesChanges(): void
    {
        $user = UserTestBuilder::anActiveUser()->withPreferencesStore($this->preferences_store)->build();
        $user->setPreference(NotificationOnOwnActionSaver::PREFERENCE_NAME, NotificationOnOwnActionSaver::VALUE_NOTIF);
        $user->setPreference(NotificationOnAllUpdatesSaver::PREFERENCE_NAME, NotificationOnAllUpdatesSaver::VALUE_NOTIF);
        $user->setPreference(Codendi_Mail_Interface::PREF_FORMAT, 'text');

        $inspector = new LayoutInspector();
        $this->expectExceptionObject(new LayoutInspectorRedirection(DisplayNotificationsController::URL));
        $this->processRequest(
            HTTPRequestBuilder::get()
                ->withParam('user_notifications_own_actions_tracker', '0')
                ->withParam('user_notifications_all_updates_tracker', '0')
                ->withParam(Codendi_Mail_Interface::PREF_FORMAT, 'html')
                ->build(),
            $inspector,
            $user,
        );
        $feedbacks = $inspector->getFeedback();
        self::assertCount(1, $feedbacks);
        self::assertEqualsCanonicalizing([
            'level'   => Feedback::INFO,
            'message' => 'Notifications preferences successfully updated',
        ], $feedbacks[0]);
        self::assertSame(NotificationOnOwnActionSaver::VALUE_NO_NOTIF, $user->getPreference(NotificationOnOwnActionSaver::PREFERENCE_NAME));
        self::assertSame(NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF, $user->getPreference(NotificationOnAllUpdatesSaver::PREFERENCE_NAME));
        self::assertSame('html', $user->getPreference(Codendi_Mail_Interface::PREF_FORMAT));
    }

    public function testItWarnsWhenNoChange(): void
    {
        $user = UserTestBuilder::anActiveUser()->withPreferencesStore($this->preferences_store)->build();
        $user->setPreference(NotificationOnOwnActionSaver::PREFERENCE_NAME, NotificationOnOwnActionSaver::VALUE_NO_NOTIF);
        $user->setPreference(NotificationOnAllUpdatesSaver::PREFERENCE_NAME, NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF);
        $user->setPreference(Codendi_Mail_Interface::PREF_FORMAT, 'html');

        $inspector = new LayoutInspector();
        $this->expectExceptionObject(new LayoutInspectorRedirection(DisplayNotificationsController::URL));
        $this->processRequest(
            HTTPRequestBuilder::get()
                ->withParam('user_notifications_own_actions_tracker', '0')
                ->withParam('user_notifications_all_updates_tracker', '0')
                ->withParam(Codendi_Mail_Interface::PREF_FORMAT, 'html')
                ->build(),
            $inspector,
            $user,
        );
        $feedbacks = $inspector->getFeedback();
        self::assertCount(1, $feedbacks);
        self::assertEqualsCanonicalizing([
            'level'   => Feedback::INFO,
            'message' => 'Nothing has changed',
        ], $feedbacks[0]);
        self::assertSame(NotificationOnOwnActionSaver::VALUE_NO_NOTIF, $user->getPreference(NotificationOnOwnActionSaver::PREFERENCE_NAME));
        self::assertSame(NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF, $user->getPreference(NotificationOnAllUpdatesSaver::PREFERENCE_NAME));
        self::assertSame('html', $user->getPreference(Codendi_Mail_Interface::PREF_FORMAT));
    }
}
