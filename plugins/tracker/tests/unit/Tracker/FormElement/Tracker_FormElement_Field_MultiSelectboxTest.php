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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PFUser;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use TrackerFactory;
use UserManager;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_MultiSelectboxTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_MultiSelectbox
     */
    private $field;

    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\MockInterface|PermissionsManager
     */
    private $permission_manager;

    /**
     * @var Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->user = Mockery::mock(PFUser::class);

        $user_manager = Mockery::mock(UserManager::class);
        $user_manager->shouldReceive('getCurrentUser')->andReturn($this->user);
        UserManager::setInstance($user_manager);

        $this->permission_manager = Mockery::mock(PermissionsManager::class);
        PermissionsManager::setInstance($this->permission_manager);

        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        TrackerFactory::setInstance($this->tracker_factory);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(101);

        $this->tracker_factory->shouldReceive('getTrackerByid')->andReturn($tracker);

        $this->user->shouldReceive('isSuperUser')->andReturnFalse();
        $this->user->shouldReceive('getUgroups')->andReturn([]);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        PermissionsManager::clearInstance();
        TrackerFactory::clearInstance();

        parent::tearDown();
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $this->field->getFieldDataFromRESTValueByField($value);
    }

    public function testItDoesNotAddNoneValueAtArtifactUpdate()
    {
        $fields_data = [
            'request_method_called' => 'artifact-update',
        ];

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertFalse(array_key_exists(1, $fields_data));
    }

    public function testItDoesNotAddNoneValueAtArtifactMasschange()
    {
        $fields_data = [
            'request_method_called' => 'artifact-masschange',
        ];

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertFalse(array_key_exists(1, $fields_data));
    }

    public function testItDoesNotAddNoneValueIfUserCannotSubmitFieldAtArtifactCreation()
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
        ];

        $this->permission_manager->shouldReceive('userHasPermission')
            ->with(
                1,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                Mockery::any()
            )
            ->once()
            ->andReturnFalse();

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertFalse(array_key_exists(1, $fields_data));
    }

    public function testItAddsNoneValueIfUserCanUpdateFieldAtArtifactCreation()
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
        ];

        $this->permission_manager->shouldReceive('userHasPermission')
            ->with(
                1,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                Mockery::any()
            )
            ->once()
            ->andReturnTrue();

        $this->permission_manager->shouldReceive('userHasPermission')
            ->with(
                1,
                'PLUGIN_TRACKER_FIELD_UPDATE',
                Mockery::any()
            )
            ->once()
            ->andReturnTrue();

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertTrue(array_key_exists(1, $fields_data));
        $this->assertSame(
            ['100'],
            $fields_data[1]
        );
    }

    public function testItDoesNotAddNoneValueIfUserCanUpdateFieldAtArtifactCreationAndAValueIsAlreadyProvided()
    {
        $fields_data = [
            'request_method_called' => 'submit-artifact',
            1 => [201],
        ];

        $this->permission_manager->shouldReceive('userHasPermission')
            ->with(
                1,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                Mockery::any()
            )
            ->once()
            ->andReturnTrue();

        $this->field->augmentDataFromRequest($fields_data);

        $this->assertSame(
            [201],
            $fields_data[1]
        );
    }

    public function testItDoesNotAddNoneValueIfFieldIsRequired()
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

        $this->permission_manager->shouldReceive('userHasPermission')
            ->with(
                1,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                Mockery::any()
            )
            ->once()
            ->andReturnTrue();

        $mandatory_field->augmentDataFromRequest($fields_data);

        $this->assertFalse(array_key_exists(1, $fields_data));
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsAreNotPresent(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue([]);
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfBindValueIdsIsAString(): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => '']);
    }

    public function testGetFieldDataFromRESTValueReturns100IfBindValueIdsIsEmpty(): void
    {
        $this->assertEquals(
            [Tracker_FormElement_Field_List::NONE_VALUE],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => []])
        );
    }

    public function testGetFieldDataFromRESTValueReturns100IfValueIs100(): void
    {
        $this->assertEquals(
            [Tracker_FormElement_Field_List::NONE_VALUE],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [100]])
        );
    }

    public function testGetFieldDataFromRESTValueThrowsExceptionIfValueIsUnknown(): void
    {
        $this->field->setBind(
            Mockery::mock(Tracker_FormElement_Field_List_Bind::class)
                ->shouldReceive(['getFieldDataFromRESTValue' => 0])
                ->getMock()
        );

        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]]);
    }

    public function testGetFieldDataFromRESTValueReturnsValue(): void
    {
        $this->field->setBind(
            Mockery::mock(Tracker_FormElement_Field_List_Bind::class)
                ->shouldReceive(['getFieldDataFromRESTValue' => 112])
                ->getMock()
        );

        $this->assertEquals(
            [112],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => [112]])
        );
    }

    public function testGetFieldDataFromRESTValueReturnsValueForDynamicGroup(): void
    {
        $this->field->setBind(
            Mockery::mock(Tracker_FormElement_Field_List_Bind::class)
                ->shouldReceive(['getFieldDataFromRESTValue' => 3])
                ->getMock()
        );

        $this->assertEquals(
            [3],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => ['103_3']])
        );
    }

    public function testGetFieldDataFromRESTValueReturnsMultipleValues(): void
    {
        $bind = Mockery::mock(Tracker_FormElement_Field_List_Bind::class);
        $this->field->setBind($bind);
        $bind->shouldReceive('getFieldDataFromRESTValue')->with('103_3')->andReturn(3);
        $bind->shouldReceive('getFieldDataFromRESTValue')->with('112')->andReturn(112);

        $this->assertEquals(
            [3, 112],
            $this->field->getFieldDataFromRESTValue(['bind_value_ids' => ['103_3', '112']])
        );
    }
}
