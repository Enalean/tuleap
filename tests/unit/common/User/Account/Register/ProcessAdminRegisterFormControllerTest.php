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

use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProcessAdminRegisterFormControllerTest extends TestCase
{
    public function testAdminShouldProvideAPassword(): void
    {
        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessAdminRegisterFormController($form_processor);
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildSiteAdministrator())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_processor->hasBeenProcessed());
        self::assertTrue($form_processor->isAdmin());
        self::assertTrue($form_processor->isPasswordNeeded());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNonAdminUsers')]
    public function testRejectForNonAdmin(\PFUser $user): void
    {
        $this->expectException(ForbiddenException::class);

        $form_processor = IProcessRegisterFormStub::buildSelf();

        $controller = new ProcessAdminRegisterFormController($form_processor);
        $controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_processor->hasBeenProcessed());
    }

    /**
     * @return \PFUser[]
     */
    public static function getNonAdminUsers(): array
    {
        return [
            [UserTestBuilder::anActiveUser()->build()],
            [UserTestBuilder::anAnonymousUser()->build()],
        ];
    }
}
