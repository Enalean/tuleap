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
use Tuleap\ForgeConfigSandbox;
use Tuleap\InviteBuddy\Invitation;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\User\LogUserStub;

final class AfterSuccessfulUserRegistrationTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testUserCreationByAdmin(): void
    {
        $user_register_mail_builder  = $this->createMock(\TuleapRegisterMail::class);
        $admin_register_mail_builder = $this->createMock(\TuleapRegisterMail::class);

        $mail = $this->createMock(\Codendi_Mail::class);
        $mail->expects(self::once())
            ->method('send')
            ->willReturn(true);

        $admin_register_mail_builder
            ->expects(self::once())
            ->method('getMail')
            ->willReturn($mail);


        $after_event_emitted = false;

        $confirmation_page = IDisplayConfirmationPageStub::buildSelf();
        $event_dispatcher  = EventDispatcherStub::withCallback(static function (AfterUserRegistrationEvent $event) use (&$after_event_emitted): AfterUserRegistrationEvent {
            $after_event_emitted = true;

            return $event;
        });

        $after = new AfterSuccessfulUserRegistration(
            $confirmation_page,
            new ConfirmationHashEmailSender(
                $user_register_mail_builder,
                'https://example.com',
            ),
            new NewUserByAdminEmailSender(
                $admin_register_mail_builder,
                'https://example.com',
            ),
            $event_dispatcher,
            LogUserStub::buildSelf(),
        );

        $after->afterSuccessfullUserRegistration(
            UserTestBuilder::buildWithDefaults(),
            HTTPRequestBuilder::get()->withParams([
                'form_send_email' => '1',
            ])->build(),
            LayoutBuilder::build(),
            'secret',
            RegisterFormContext::forAdmin(),
        );

        self::assertTrue($after_event_emitted);
        self::assertTrue($confirmation_page->hasConfirmationForAdminBeenDisplayed());
    }

    public function testWaitForApproval(): void
    {
        \ForgeConfig::set(\User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 1);

        $user_register_mail_builder  = $this->createMock(\TuleapRegisterMail::class);
        $admin_register_mail_builder = $this->createMock(\TuleapRegisterMail::class);

        $after_event_emitted = false;

        $confirmation_page = IDisplayConfirmationPageStub::buildSelf();
        $event_dispatcher  = EventDispatcherStub::withCallback(static function (AfterUserRegistrationEvent $event) use (&$after_event_emitted): AfterUserRegistrationEvent {
            $after_event_emitted = true;

            return $event;
        });

        $after = new AfterSuccessfulUserRegistration(
            $confirmation_page,
            new ConfirmationHashEmailSender(
                $user_register_mail_builder,
                'https://example.com',
            ),
            new NewUserByAdminEmailSender(
                $admin_register_mail_builder,
                'https://example.com',
            ),
            $event_dispatcher,
            LogUserStub::buildSelf(),
        );

        $after->afterSuccessfullUserRegistration(
            UserTestBuilder::buildWithDefaults(),
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            'secret',
            RegisterFormContext::forAnonymous(true, null),
        );

        self::assertTrue($after_event_emitted);
        self::assertTrue($confirmation_page->hasWaitForApprovaleBeenDisplayed());
    }

    public function testConfirmationLinkSent(): void
    {
        \ForgeConfig::set(\User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);

        $user_register_mail_builder  = $this->createMock(\TuleapRegisterMail::class);
        $admin_register_mail_builder = $this->createMock(\TuleapRegisterMail::class);

        $mail = $this->createMock(\Codendi_Mail::class);
        $mail->expects(self::once())
            ->method('send')
            ->willReturn(true);

        $user_register_mail_builder
            ->expects(self::once())
            ->method('getMail')
            ->willReturn($mail);

        $after_event_emitted = false;

        $confirmation_page = IDisplayConfirmationPageStub::buildSelf();
        $event_dispatcher  = EventDispatcherStub::withCallback(static function (AfterUserRegistrationEvent $event) use (&$after_event_emitted): AfterUserRegistrationEvent {
            $after_event_emitted = true;

            return $event;
        });

        $after = new AfterSuccessfulUserRegistration(
            $confirmation_page,
            new ConfirmationHashEmailSender(
                $user_register_mail_builder,
                'https://example.com',
            ),
            new NewUserByAdminEmailSender(
                $admin_register_mail_builder,
                'https://example.com',
            ),
            $event_dispatcher,
            LogUserStub::buildSelf(),
        );

        $after->afterSuccessfullUserRegistration(
            UserTestBuilder::buildWithDefaults(),
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            'secret',
            RegisterFormContext::forAnonymous(true, null),
        );

        self::assertTrue($after_event_emitted);
        self::assertTrue($confirmation_page->hasConfirmationLinkSentBeenDisplayed());
    }

    public function testConfirmationLinkError(): void
    {
        \ForgeConfig::set(\User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);

        $user_register_mail_builder  = $this->createMock(\TuleapRegisterMail::class);
        $admin_register_mail_builder = $this->createMock(\TuleapRegisterMail::class);

        $mail = $this->createMock(\Codendi_Mail::class);
        $mail->expects(self::once())
            ->method('send')
            ->willReturn(false);

        $user_register_mail_builder
            ->expects(self::once())
            ->method('getMail')
            ->willReturn($mail);

        $after_event_emitted = false;

        $confirmation_page = IDisplayConfirmationPageStub::buildSelf();
        $event_dispatcher  = EventDispatcherStub::withCallback(static function (AfterUserRegistrationEvent $event) use (&$after_event_emitted): AfterUserRegistrationEvent {
            $after_event_emitted = true;

            return $event;
        });

        $after = new AfterSuccessfulUserRegistration(
            $confirmation_page,
            new ConfirmationHashEmailSender(
                $user_register_mail_builder,
                'https://example.com',
            ),
            new NewUserByAdminEmailSender(
                $admin_register_mail_builder,
                'https://example.com',
            ),
            $event_dispatcher,
            LogUserStub::buildSelf(),
        );

        $after->afterSuccessfullUserRegistration(
            UserTestBuilder::buildWithDefaults(),
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            'secret',
            RegisterFormContext::forAnonymous(true, null),
        );

        self::assertTrue($after_event_emitted);
        self::assertTrue($confirmation_page->hasConfirmationLinkErrorBeenDisplayed());
    }

    public function testItDoesNotDisplaySentAConfirmationLinkIfUserHasAnInvitationBecauseWeAlreadyKnowThatTheirEmailIsValid(): void
    {
        \ForgeConfig::set(\User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);

        $user_register_mail_builder  = $this->createMock(\TuleapRegisterMail::class);
        $admin_register_mail_builder = $this->createMock(\TuleapRegisterMail::class);

        $after_event_emitted = false;

        $confirmation_page = IDisplayConfirmationPageStub::buildSelf();
        $event_dispatcher  = EventDispatcherStub::withCallback(static function (AfterUserRegistrationEvent $event) use (&$after_event_emitted): AfterUserRegistrationEvent {
            $after_event_emitted = true;

            return $event;
        });

        $log_user = LogUserStub::buildSelf();
        $after    = new AfterSuccessfulUserRegistration(
            $confirmation_page,
            new ConfirmationHashEmailSender(
                $user_register_mail_builder,
                'https://example.com',
            ),
            new NewUserByAdminEmailSender(
                $admin_register_mail_builder,
                'https://example.com',
            ),
            $event_dispatcher,
            $log_user,
        );

        $inspector = new LayoutInspector();

        $after->afterSuccessfullUserRegistration(
            UserTestBuilder::buildWithDefaults(),
            HTTPRequestBuilder::get()
                ->withParams(['form_pw' => 'secret', 'invitation-token' => 'tlp-invite-13.abc'])
                ->build(),
            LayoutBuilder::buildWithInspector($inspector),
            'secret',
            RegisterFormContext::forAnonymous(true, InvitationToEmail::fromInvitation(new Invitation(1, 'jdoe@example.com', null, 101, null), new ConcealedString('secret'))),
        );

        self::assertTrue($after_event_emitted);
        self::assertFalse($confirmation_page->hasConfirmationLinkSentBeenDisplayed());
        self::assertTrue($log_user->hasBeenLoggedIn());
        self::assertEquals('/my/', $inspector->getRedirectUrl());
    }
}
