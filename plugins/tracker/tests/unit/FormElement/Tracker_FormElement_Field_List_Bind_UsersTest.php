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

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Tracker\Import\Spotter;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_Bind_UsersTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        Spotter::clearInstance();
        parent::tearDown();
    }

    public function testGetFieldData(): void
    {
        $bv1 = ListUserValueBuilder::aUserWithId(138)->withUserName('john.smith')->build();
        $bv2 = ListUserValueBuilder::aUserWithId(138)->withUserName('sam.anderson')->build();

        $values = [108 => $bv1, 110 => $bv2];
        $f      = $this->getBindUsersField($values);
        $f->shouldReceive('getAllValues')->andReturn($values);
        $this->assertEquals('108', $f->getFieldData('john.smith', false));
    }

    public function testGetFieldDataMultiple(): void
    {
        $bv1    = ListUserValueBuilder::aUserWithId(138)->withUserName('john.smith')->build();
        $bv2    = ListUserValueBuilder::aUserWithId(138)->withUserName('sam.anderson')->build();
        $bv3    = ListUserValueBuilder::aUserWithId(138)->withUserName('tom.brown')->build();
        $bv4    = ListUserValueBuilder::aUserWithId(138)->withUserName('patty.smith')->build();
        $values = [108 => $bv1, 110 => $bv2, 113 => $bv3, 115 => $bv4];
        $f      = $this->getBindUsersField($values);
        $f->shouldReceive('getAllValues')->andReturn($values);
        $res = [108, 113];
        $this->assertEquals($res, $f->getFieldData('john.smith,tom.brown', true));
    }

    public function testGetRecipients(): void
    {
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->shouldReceive('getListValues')->andReturn(
            [
                ListUserValueBuilder::aUserWithId(138)->withUserName('u1')->build(),
                ListUserValueBuilder::aUserWithId(831)->withUserName('u2')->build(),
            ]
        );

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getId')->andReturn(123);
        $value_function = 'project_members';
        $default_values = $decorators = '';

        $users = new Tracker_FormElement_Field_List_Bind_Users(new DatabaseUUIDV7Factory(), $field, $value_function, $default_values, $decorators);
        $this->assertEquals(['u1', 'u2'], $users->getRecipients($changeset_value));
    }

    public function testFormatChangesetValueNoneValue(): void
    {
        $value  = ListUserValueBuilder::noneUser()->build();
        $value2 = ListUserValueBuilder::aUserWithId(1)->build();
        $value3 = ListUserValueBuilder::aUserWithId(103)->build();

        $field  = $this->getBindUsersField([$value]);
        $field2 = $this->getBindUsersField([$value2]);
        $field3 = $this->getBindUsersField([$value3]);

        $this->assertEquals('', $field->formatChangesetValue($value));
        $this->assertNotEquals('', $field2->formatChangesetValue($value2));
        $this->assertNotEquals('', $field3->formatChangesetValue($value3));
    }

    public function testItVerifiesAValueExist(): void
    {
        $user_manager = Mockery::mock(UserManager::class);
        $user_manager->shouldReceive('getUserById')->withArgs([101])->andReturn(\Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build());
        $user_manager->shouldReceive('getUserById')->withArgs([102])->andReturn(\Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build());
        $bind_users = Mockery::mock(Tracker_FormElement_Field_List_Bind_Users::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $bind_users->shouldReceive('getAllValues')->andReturn([101 => 'user1']);
        $bind_users->shouldReceive('getUserManager')->andReturn($user_manager);

        $this->assertTrue($bind_users->isExistingValue(101));
        $this->assertFalse($bind_users->isExistingValue(102));

        $import_spotter = Mockery::mock(Spotter::class);
        $import_spotter->shouldReceive('isImportRunning')->andReturn(true);
        Spotter::setInstance($import_spotter);

        $this->assertTrue($bind_users->isExistingValue(101));
        $this->assertTrue($bind_users->isExistingValue(102));

        $this->assertTrue($bind_users->isExistingValue(101));
        $this->assertTrue($bind_users->isExistingValue(102));
    }

    /**
     * @return \Mockery\Mock|Tracker_FormElement_Field_List_Bind_Users
     */
    protected function getBindUsersField(array $values)
    {
        $field = $is_rank_alpha = $default_values = $decorators = '';

        return Mockery::mock(
            Tracker_FormElement_Field_List_Bind_Users::class,
            [new \Tuleap\DB\DatabaseUUIDV7Factory(), $field, $is_rank_alpha, $values, $default_values, $decorators]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testRetrievingDefaultRESTValuesDoesNotHitTheDBWhenNoDefaultValuesIsSet(): void
    {
        $list_field     = Mockery::mock(Tracker_FormElement_Field_List::class);
        $default_values = [];

        $bind_users = new Tracker_FormElement_Field_List_Bind_Users(new \Tuleap\DB\DatabaseUUIDV7Factory(), $list_field, '', $default_values, []);

        $this->assertEmpty($bind_users->getDefaultValues());
        $this->assertEmpty($bind_users->getDefaultRESTValues());
    }
}
