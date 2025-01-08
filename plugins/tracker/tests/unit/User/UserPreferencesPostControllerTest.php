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
use Tuleap\User\Account\DisplayNotificationsController;

final class UserPreferencesPostControllerTest extends TestCase
{
    private function processRequest(HTTPRequest $request, LayoutInspector $inspector, PFUser $user): void
    {
        $controller = new UserPreferencesPostController(ProvideAndRetrieveUserStub::build($user), CSRFSynchronizerTokenStub::buildSelf());
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
        $user = $this->createMock(PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getPreference')->willReturnCallback(static fn(string $pref) => match ($pref) {
            NotificationOnOwnActionPreference::PREFERENCE_NAME => '1',
            Codendi_Mail_Interface::PREF_FORMAT                => 'text',
        });
        $user->method('setPreference')->withConsecutive(
            [NotificationOnOwnActionPreference::PREFERENCE_NAME, '0'],
            [Codendi_Mail_Interface::PREF_FORMAT, 'html'],
        );

        $inspector = new LayoutInspector();
        $this->expectExceptionObject(new LayoutInspectorRedirection(DisplayNotificationsController::URL));
        $this->processRequest(
            HTTPRequestBuilder::get()
                ->withParam(NotificationOnOwnActionPreference::PREFERENCE_NAME, '0')
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
    }

    public function testItDoesNotApplyChanges(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getPreference')->willReturnCallback(static fn(string $pref) => match ($pref) {
            NotificationOnOwnActionPreference::PREFERENCE_NAME => '0',
            Codendi_Mail_Interface::PREF_FORMAT                => 'html',
        });

        $inspector = new LayoutInspector();
        $this->expectExceptionObject(new LayoutInspectorRedirection(DisplayNotificationsController::URL));
        $this->processRequest(
            HTTPRequestBuilder::get()
                ->withParam(NotificationOnOwnActionPreference::PREFERENCE_NAME, '0')
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
    }
}
