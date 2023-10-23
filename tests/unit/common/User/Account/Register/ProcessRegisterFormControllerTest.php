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
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\InviteBuddy\InvitationToEmail;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\RetrieveUserByEmailStub;
use Tuleap\User\Account\RegistrationGuardEvent;

class ProcessRegisterFormControllerTest extends TestCase
{
    public function testPasswordIsNeededByDefault(): void
    {
        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withInvitation(
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->to('jdoe@example.com')
                        ->build(),
                    new ConcealedString('secret'),
                ),
            ),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_processor->hasBeenProcessed());
        self::assertFalse($form_processor->isAdmin());
        self::assertTrue($form_processor->isPasswordNeeded());
    }

    public function testWhenPasswordIsNotNeeded(): void
    {
        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withCallback(
                static function (Dispatchable $event): object {
                    if ($event instanceof BeforeUserRegistrationEvent) {
                        $event->noNeedForPassword();
                    }

                    return $event;
                }
            ),
            IExtractInvitationToEmailStub::withInvitation(
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->to('jdoe@example.com')
                        ->build(),
                    new ConcealedString('secret'),
                ),
            ),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_processor->hasBeenProcessed());
        self::assertFalse($form_processor->isAdmin());
        self::assertFalse($form_processor->isPasswordNeeded());
    }

    public function testRejectWhenRegistrationIsNotPossible(): void
    {
        $this->expectException(ForbiddenException::class);

        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withCallback(
                static function (Dispatchable $event): object {
                    if ($event instanceof RegistrationGuardEvent) {
                        $event->disableRegistration();
                    }

                    return $event;
                }
            ),
            IExtractInvitationToEmailStub::withInvitation(
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->to('jdoe@example.com')
                        ->build(),
                    new ConcealedString('secret'),
                ),
            ),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_processor->hasBeenProcessed());
    }

    public function testRejectWhenCurrentUserIsAlreadyLoggedIn(): void
    {
        $this->expectException(ForbiddenException::class);

        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withInvitation(
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->to('jdoe@example.com')
                        ->build(),
                    new ConcealedString('secret'),
                ),
            ),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anActiveUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_processor->hasBeenProcessed());
    }

    public function testRejectWhenInvitationHasAlreadyBeenUsed(): void
    {
        $this->expectException(ForbiddenException::class);

        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withInvitation(
                InvitationToEmail::fromInvitation(
                    InvitationTestBuilder::aSentInvitation(1)
                        ->to('jdoe@example.com')
                        ->withCreatedUserId(201)
                        ->build(),
                    new ConcealedString('secret'),
                ),
            ),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_processor->hasBeenProcessed());
    }

    public function testWithNoInvitation(): void
    {
        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessRegisterFormController(
            $form_processor,
            EventDispatcherStub::withIdentityCallback(),
            IExtractInvitationToEmailStub::withoutInvitation(),
            RetrieveUserByEmailStub::withNoUser(),
        );
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::anAnonymousUser()->build())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_processor->hasBeenProcessed());
        self::assertFalse($form_processor->isAdmin());
        self::assertTrue($form_processor->isPasswordNeeded());
    }
}
