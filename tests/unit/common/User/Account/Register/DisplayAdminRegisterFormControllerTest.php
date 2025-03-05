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

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DisplayAdminRegisterFormControllerTest extends TestCase
{
    public function testAdminShouldProvideAPassword(): void
    {
        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayAdminRegisterFormController($form_displayer);
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildSiteAdministrator())->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertTrue($form_displayer->hasBeenDisplayed());
        self::assertTrue($form_displayer->isAdmin());
        self::assertTrue($form_displayer->isPasswordNeeded());
    }

    #[DataProvider('getNonAdminUsers')]
    public function testRejectForNonAdmin(\PFUser $user): void
    {
        $this->expectException(ForbiddenException::class);

        $form_displayer = IDisplayRegisterFormStub::buildSelf();

        $controller = new DisplayAdminRegisterFormController($form_displayer);
        $controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            $this->createMock(BaseLayout::class),
            [],
        );

        self::assertFalse($form_displayer->hasBeenDisplayed());
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
