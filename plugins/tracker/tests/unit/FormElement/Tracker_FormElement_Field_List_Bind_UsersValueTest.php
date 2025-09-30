<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use UserHelper;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_Bind_UsersValueTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    use GlobalLanguageMock;

    public function testGetLabel(): void
    {
        $user_helper = $this->createMock(UserHelper::class);
        $user_helper->method('getDisplayNameFromUserId')->with(12)->willReturn('John Smith');

        $bind_value = $this->getListBindUserValue();

        $bind_value->method('getUserHelper')->willReturn($user_helper);

        self::assertEquals('John Smith', $bind_value->getLabel());
    }

    public function testGetUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->with(12)->willReturn($user);

        $bind_value = $this->getListBindUserValue();
        $bind_value->method('getUserManager')->willReturn($user_manager);

        self::assertEquals($user, $bind_value->getUser());
    }

    public function testItReturnsTheUserNameAsWell(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withUserName('neo')
            ->withRealName('Le roi arthur')
            ->withAvatarUrl('https://example.com')
            ->build();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->with(12)->willReturn($user);

        $user_helper = $this->createMock(UserHelper::class);
        $user_helper->method('getDisplayNameFromUserId')->willReturn('Thomas A. Anderson (neo)');

        $value = $this->getListBindUserValue();
        $value->method('getUserManager')->willReturn($user_manager);
        $value->method('getUserHelper')->willReturn($user_helper);

        $json = $value->fetchFormattedForJson();
        self::assertEquals(
            [
                'id'           => '12',
                'label'        => 'Thomas A. Anderson (neo)',
                'is_hidden'    => false,
                'username'     => 'neo',
                'realname'     => 'Le roi arthur',
                'avatar_url'   => 'https://example.com',
                'display_name' => 'Le roi arthur (neo)',
            ],
            $json
        );
    }

    public function testItReturnsNullForGetJsonIfUserIsNone(): void
    {
        $value = ListUserValueBuilder::noneUser()->build();
        $json  = $value->getJsonValue();
        self::assertNull($json);
    }

    private function getListBindUserValue(): Tracker_FormElement_Field_List_Bind_UsersValue&MockObject
    {
        $bind = $this->createPartialMock(Tracker_FormElement_Field_List_Bind_UsersValue::class, [
            'getId', 'getUserManager', 'getUserHelper',
        ]);
        $bind->method('getId')->willReturn(12);
        return $bind;
    }
}
