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

use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\IGenerateMailConfirmationCodeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RegisterFormProcessorTest extends TestCase
{
    public function testHappyPath(): void
    {
        $after          = AfterSuccessfulUserRegistrationHandlerStub::buildSelf();
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $processor = new RegisterFormProcessor(
            IValidateFormAndCreateUserStub::withCreatedUser(UserTestBuilder::buildWithDefaults()),
            IGenerateMailConfirmationCodeStub::fromString('secret'),
            $after,
            $form_displayer,
        );

        $processor->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            RegisterFormContext::forAdmin(),
        );

        self::assertTrue($after->hasBeenCalled());
        self::assertFalse($form_displayer->hasBeenDisplayedWithPossibleIssue());
    }

    public function testRedisplayFormInCaseOfError(): void
    {
        $after          = AfterSuccessfulUserRegistrationHandlerStub::buildSelf();
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $processor = new RegisterFormProcessor(
            IValidateFormAndCreateUserStub::withError(),
            IGenerateMailConfirmationCodeStub::fromString('secret'),
            $after,
            $form_displayer,
        );

        $processor->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            RegisterFormContext::forAdmin(),
        );

        self::assertFalse($after->hasBeenCalled());
        self::assertTrue($form_displayer->hasBeenDisplayedWithPossibleIssue());
    }
}
