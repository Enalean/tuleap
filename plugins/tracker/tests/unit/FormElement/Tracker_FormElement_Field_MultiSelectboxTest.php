<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use PermissionsManager;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_MultiSelectboxTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_FormElement_Field_MultiSelectbox $field;
    private PermissionsManager&MockObject $permission_manager;

    protected function setUp(): void
    {
        $this->field = new Tracker_FormElement_Field_MultiSelectbox(
            1,
            101,
            null,
            'field_msb',
            'Field MSB',
            '',
            1,
            'P',
            false,
            '',
            1
        );

        $user = $this->createPartialMock(PFUser::class, ['isSuperUSer', 'getUgroups', 'getId']);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(101);
        UserManager::instance()->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));

        $this->permission_manager = $this->createMock(PermissionsManager::class);
        PermissionsManager::setInstance($this->permission_manager);

        $tracker_factory = $this->createMock(TrackerFactory::class);
        TrackerFactory::setInstance($tracker_factory);

        $tracker = TrackerTestBuilder::aTracker()->withProject(ProjectTestBuilder::aProject()->withId(101)->build())->build();
        $tracker_factory->method('getTrackerById')->willReturn($tracker);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        PermissionsManager::clearInstance();
        TrackerFactory::clearInstance();
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $this->field->getFieldDataFromRESTValueByField($value);
    }

    public function testItDoesNotAddNoneValueAtArtifactUpdate(): void
    {
        $fields_data = [
            'request_method_called' => 'artifact-update',
        ];

        $this->field->augmentDataFromRequest($fields_data);

        self::assertFalse(array_key_exists(1, $fields_data));
    }

    public function testItDoesNotAddNoneValueAtArtifactMasschange(): void
    {
        $fields_data = [
            'request_method_called' => 'artifact-masschange',
        ];

        $this->field->augmentDataFromRequest($fields_data);

        self::assertFalse(array_key_exists(1, $fields_data));
    }

    public function testItDoesNotAddNoneValueIfUserCannotSubmitFieldAtArtifactCreation(): void
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
        ];

        $this->permission_manager->expects($this->once())->method('userHasPermission')
            ->with(1, 'PLUGIN_TRACKER_FIELD_SUBMIT', self::anything())
            ->willReturn(false);

        $this->field->augmentDataFromRequest($fields_data);

        self::assertFalse(array_key_exists(1, $fields_data));
    }

    public function testItAddsNoneValueIfUserCanUpdateFieldAtArtifactCreation(): void
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
        ];

        $this->permission_manager->expects($this->exactly(2))->method('userHasPermission')
            ->with(1, self::isString(), self::anything())
            ->willReturnCallback(static fn(int $object_id, string $permission) => match ($permission) {
                'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_UPDATE' => true,
            });

        $this->field->augmentDataFromRequest($fields_data);

        self::assertTrue(array_key_exists(1, $fields_data));
        self::assertSame(
            ['100'],
            $fields_data[1]
        );
    }

    public function testItDoesNotAddNoneValueIfUserCanUpdateFieldAtArtifactCreationAndAValueIsAlreadyProvided(): void
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
            1                       => [201],
        ];

        $this->permission_manager->expects($this->once())->method('userHasPermission')
            ->with(1, 'PLUGIN_TRACKER_FIELD_SUBMIT', self::anything())
            ->willReturn(true);

        $this->field->augmentDataFromRequest($fields_data);

        self::assertSame(
            [201],
            $fields_data[1]
        );
    }

    public function testItDoesNotAddNoneValueIfFieldIsRequired(): void
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
        ];

        $mandatory_field = new Tracker_FormElement_Field_MultiSelectbox(
            1,
            101,
            null,
            'field_msb',
            'Field MSB',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->permission_manager->expects($this->once())->method('userHasPermission')
            ->with(1, 'PLUGIN_TRACKER_FIELD_SUBMIT', self::anything())
            ->willReturn(true);

        $mandatory_field->augmentDataFromRequest($fields_data);

        self::assertFalse(array_key_exists(1, $fields_data));
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsAreNotPresent(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue([]);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsIsAString(): void
    {
        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => '']);
    }

    public function testGetFieldDataFromRESTValueReturns100IfBindValueIdsIsEmpty(): void
    {
        self::assertEquals(
            [Tracker_FormElement_Field_List::NONE_VALUE],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => []])
        );
    }

    public function testGetFieldDataFromRESTValueReturns100IfValueIs100(): void
    {
        self::assertEquals(
            [Tracker_FormElement_Field_List::NONE_VALUE],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [100]])
        );
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfValueIsUnknown(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $bind->method('getFieldDataFromRESTValue')->willReturn(0);
        $this->field->setBind($bind);

        $this->expectException(Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]]);
    }

    public function testGetFieldDataFromRESTValueReturnsValue(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $bind->method('getFieldDataFromRESTValue')->willReturn(112);
        $this->field->setBind($bind);

        self::assertEquals(
            [112],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]])
        );
    }

    public function testGetFieldDataFromRESTValueReturnsValueForDynamicGroup(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $bind->method('getFieldDataFromRESTValue')->willReturn(3);
        $this->field->setBind($bind);

        self::assertEquals(
            [3],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => ['103_3']])
        );
    }

    public function testGetFieldDataFromRESTValueReturnsMultipleValues(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind::class);
        $this->field->setBind($bind);
        $bind->method('getFieldDataFromRESTValue')->willReturnCallback(static fn($value) => match ($value) {
            '103_3' => 3,
            '112'   => 112,
        });

        self::assertEquals(
            [3, 112],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => ['103_3', '112']])
        );
    }
}
