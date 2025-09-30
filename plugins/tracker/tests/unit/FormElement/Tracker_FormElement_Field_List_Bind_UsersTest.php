<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Import\Spotter;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_Bind_UsersTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Spotter::clearInstance();
    }

    public function testGetFieldData(): void
    {
        $bv1 = ListUserValueBuilder::aUserWithId(138)->withUserName('john.smith')->build();
        $bv2 = ListUserValueBuilder::aUserWithId(138)->withUserName('sam.anderson')->build();

        $values = [108 => $bv1, 110 => $bv2];
        $bind   = $this->getBindUsersField($values);
        $bind->method('getAllValues')->willReturn($values);
        self::assertEquals('108', $bind->getFieldData('john.smith', false));
    }

    public function testGetFieldDataMultiple(): void
    {
        $bv1    = ListUserValueBuilder::aUserWithId(138)->withUserName('john.smith')->build();
        $bv2    = ListUserValueBuilder::aUserWithId(138)->withUserName('sam.anderson')->build();
        $bv3    = ListUserValueBuilder::aUserWithId(138)->withUserName('tom.brown')->build();
        $bv4    = ListUserValueBuilder::aUserWithId(138)->withUserName('patty.smith')->build();
        $values = [108 => $bv1, 110 => $bv2, 113 => $bv3, 115 => $bv4];
        $bind   = $this->getBindUsersField($values);
        $bind->method('getAllValues')->willReturn($values);
        $res = [108, 113];
        self::assertEquals($res, $bind->getFieldData('john.smith,tom.brown', true));
    }

    public function testGetRecipients(): void
    {
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->method('getListValues')->willReturn([
            ListUserValueBuilder::aUserWithId(138)->withUserName('u1')->build(),
            ListUserValueBuilder::aUserWithId(831)->withUserName('u2')->build(),
        ]);

        $field          = SelectboxFieldBuilder::aSelectboxField(123)->build();
        $value_function = 'project_members';
        $default_values = $decorators = '';

        $users = new Tracker_FormElement_Field_List_Bind_Users(new DatabaseUUIDV7Factory(), $field, $value_function, $default_values, $decorators);
        self::assertEquals(['u1', 'u2'], $users->getRecipients($changeset_value));
    }

    public function testFormatChangesetValueNoneValue(): void
    {
        $value  = ListUserValueBuilder::noneUser()->build();
        $value2 = ListUserValueBuilder::aUserWithId(1)->build();
        $value3 = ListUserValueBuilder::aUserWithId(103)->build();

        $field  = $this->getBindUsersField([$value]);
        $field2 = $this->getBindUsersField([$value2]);
        $field3 = $this->getBindUsersField([$value3]);

        self::assertEquals('', $field->formatChangesetValue($value));
        self::assertNotEquals('', $field2->formatChangesetValue($value2));
        self::assertNotEquals('', $field3->formatChangesetValue($value3));
    }

    public function testItVerifiesAValueExist(): void
    {
        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->willReturnCallback(static fn(int $id) => match ($id) {
            101, 102 => UserTestBuilder::anActiveUser()->build(),
        });
        $bind_users = $this->createPartialMock(Tracker_FormElement_Field_List_Bind_Users::class, ['getAllValues', 'getUserManager']);
        $bind_users->method('getAllValues')->willReturn([101 => 'user1']);
        $bind_users->method('getUserManager')->willReturn($user_manager);

        self::assertTrue($bind_users->isExistingValue(101));
        self::assertFalse($bind_users->isExistingValue(102));

        $import_spotter = $this->createMock(Spotter::class);
        $import_spotter->method('isImportRunning')->willReturn(true);
        Spotter::setInstance($import_spotter);

        self::assertTrue($bind_users->isExistingValue(101));
        self::assertTrue($bind_users->isExistingValue(102));

        self::assertTrue($bind_users->isExistingValue(101));
        self::assertTrue($bind_users->isExistingValue(102));
    }

    protected function getBindUsersField(array $values): Tracker_FormElement_Field_List_Bind_Users&MockObject
    {
        return $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Users::class)
            ->setConstructorArgs([new DatabaseUUIDV7Factory(), '', '', $values, '', ''])
            ->onlyMethods(['getAllValues'])
            ->getMock();
    }

    public function testRetrievingDefaultRESTValuesDoesNotHitTheDBWhenNoDefaultValuesIsSet(): void
    {
        $list_field     = SelectboxFieldBuilder::aSelectboxField(123)->build();
        $default_values = [];

        $bind_users = new Tracker_FormElement_Field_List_Bind_Users(new DatabaseUUIDV7Factory(), $list_field, '', $default_values, []);

        self::assertEmpty($bind_users->getDefaultValues());
        self::assertEmpty($bind_users->getDefaultRESTValues());
    }
}
