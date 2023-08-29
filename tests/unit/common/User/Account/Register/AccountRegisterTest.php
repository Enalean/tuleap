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

use Tuleap\GlobalLanguageMock;
use Tuleap\InviteBuddy\InvitationSuccessFeedbackStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\ICreateAccountStub;

class AccountRegisterTest extends TestCase
{
    use GlobalLanguageMock;

    public function testItReturnsNullIfAccountCreationFails(): void
    {
        $invitation_success_feedback = InvitationSuccessFeedbackStub::buildSelf();

        $account = new AccountRegister(
            ICreateAccountStub::withInvalidCreation(),
            $invitation_success_feedback,
        );

        self::assertNull(
            $account->register(
                'loginname',
                null,
                'realname',
                'register_purpose',
                'email',
                'status',
                'confirm_hash',
                'mail_site',
                'mail_va',
                'timezone',
                'lang_id',
                'expiry_date',
                RegisterFormContext::forAdmin(),
            )
        );
        self::assertFalse($invitation_success_feedback->hasBeenCalled());
    }

    public function testHappyPath(): void
    {
        $invitation_success_feedback = InvitationSuccessFeedbackStub::buildSelf();

        $created_user = UserTestBuilder::buildWithDefaults();
        $account      = new AccountRegister(
            ICreateAccountStub::withSuccessfullyCreatedUser($created_user),
            $invitation_success_feedback,
        );

        self::assertEquals(
            $created_user,
            $account->register(
                'loginname',
                null,
                'realname',
                'register_purpose',
                'email',
                'status',
                'confirm_hash',
                'mail_site',
                'mail_va',
                'timezone',
                'lang_id',
                'expiry_date',
                RegisterFormContext::forAdmin(),
            )
        );
        self::assertTrue($invitation_success_feedback->hasBeenCalledWith($created_user));
    }
}
