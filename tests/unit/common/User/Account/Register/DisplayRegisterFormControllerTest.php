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

namespace Tuleap\User\Account\Register;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Event\Dispatchable;
use Tuleap\InviteBuddy\Invitation;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\RetrieveUserByEmailStub;
use Tuleap\User\Account\RegistrationGuardEvent;

final class DisplayRegisterFormControllerTest extends TestCase
{
    public function testPasswordIsNeededByDefault(): void
    {
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayRegisterFormController(
            $form_displayer,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withInvitation(InvitationToEmail::fromInvitation(new Invitation(1, 'jdoe@example.com', 102), new ConcealedString('secret'))),
            RetrieveUserByEmailStub::withNoUser(),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_displayer->hasBeenDisplayed());
        self::assertFalse($form_displayer->isAdmin());
        self::assertTrue($form_displayer->isPasswordNeeded());
    }

    public function testWhenPasswordIsNotNeeded(): void
    {
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayRegisterFormController(
            $form_displayer,
            EventDispatcherStub::withCallback(
                static function (Dispatchable $event): object {
                    if ($event instanceof BeforeUserRegistrationEvent) {
                        $event->noNeedForPassword();
                    }

                    return $event;
                }
            ),
            IExtractInvitationToEmailStub::withInvitation(InvitationToEmail::fromInvitation(new Invitation(1, 'jdoe@example.com', 102), new ConcealedString('secret'))),
            RetrieveUserByEmailStub::withNoUser(),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_displayer->hasBeenDisplayed());
        self::assertFalse($form_displayer->isAdmin());
        self::assertFalse($form_displayer->isPasswordNeeded());
    }

    public function testRejectWhenRegistrationIsNotPossible(): void
    {
        $this->expectException(ForbiddenException::class);

        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayRegisterFormController(
            $form_displayer,
            EventDispatcherStub::withCallback(
                static function (Dispatchable $event): object {
                    if ($event instanceof RegistrationGuardEvent) {
                        $event->disableRegistration();
                    }

                    return $event;
                }
            ),
            IExtractInvitationToEmailStub::withInvitation(InvitationToEmail::fromInvitation(new Invitation(1, 'jdoe@example.com', 102), new ConcealedString('secret'))),
            RetrieveUserByEmailStub::withNoUser(),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_displayer->hasBeenDisplayed());
    }

    public function testRedirectToMyWhenCurrentUserIsAlreadyLoggedIn(): void
    {
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayRegisterFormController(
            $form_displayer,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withInvitation(InvitationToEmail::fromInvitation(new Invitation(1, 'jdoe@example.com', 102), new ConcealedString('secret'))),
            RetrieveUserByEmailStub::withNoUser(),
        );

        $inspector = new LayoutInspector();

        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anActiveUser()->build())->build(),
            LayoutBuilder::buildWithInspector($inspector),
            [],
        );

        self::assertFalse($form_displayer->hasBeenDisplayed());
        self::assertEquals('/my/', $inspector->getRedirectUrl());
    }

    public function testRedirectToLoginWhenInvitationHasAlreadyBeenUsed(): void
    {
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayRegisterFormController(
            $form_displayer,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withInvitation(InvitationToEmail::fromInvitation(new Invitation(1, 'jdoe@example.com', 102), new ConcealedString('secret'))),
            RetrieveUserByEmailStub::withUser(UserTestBuilder::buildWithDefaults()),
        );

        $inspector = new LayoutInspector();

        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            LayoutBuilder::buildWithInspector($inspector),
            [],
        );

        self::assertFalse($form_displayer->hasBeenDisplayed());
        self::assertEquals('/account/login.php', $inspector->getRedirectUrl());
    }

    public function testWithoutInvitation(): void
    {
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayRegisterFormController(
            $form_displayer,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withoutInvitation(),
            RetrieveUserByEmailStub::withNoUser(),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_displayer->hasBeenDisplayed());
        self::assertFalse($form_displayer->isAdmin());
        self::assertTrue($form_displayer->isPasswordNeeded());
    }
}
