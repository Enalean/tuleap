<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_Static_ValueDao;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenter;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenterBuilder;

class FormElementListValueAdminViewPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FormElementListValueAdminViewPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \Mockery\MockInterface|Tracker_FormElement_Field_List_Bind_Static_ValueDao
     */
    private $value_dao;
    /**
     * @var \Mockery\MockInterface|Tracker_FormElement_Field
     */
    private $field;

    protected function setUp(): void
    {
        $this->field             = \Mockery::mock(Tracker_FormElement_Field::class);
        $this->field->shouldReceive('getTrackerId')->andReturn(5);

        $this->value_dao         = \Mockery::mock(Tracker_FormElement_Field_List_Bind_Static_ValueDao::class);
        $this->presenter_builder = new FormElementListValueAdminViewPresenterBuilder($this->value_dao);
    }

    public function testBuildPresenter(): void
    {
        $value = \Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value->shouldReceive('getId')->andReturn(666);
        $value->shouldReceive('getLabel')->andReturn("label");
        $value->shouldReceive('isHidden')->andReturn(false);

        $decorator = \Mockery::mock(ColorpickerMountPointPresenter::class);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            true,
            false,
            "/plugins/tracker/?tracker=5&func=admin-formElement-update&formElement=111&bind-update=1&bind%5Bdelete%5D=666",
            'Show/hide this value',
            'Show/hide this value',
            '',
            false
        );

        $this->field->shouldReceive('getId')->andReturn(111);

        $this->value_dao->shouldReceive('canValueBeHidden')->andReturn(true);
        $this->value_dao->shouldReceive('canValueBeDeleted')->andReturn(false);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, false);

        $this->assertEquals($expected_result, $result);
    }

    public function testBuildPresenterNoneValueCantBeDeletedOrId(): void
    {
        $value = \Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value->shouldReceive('getId')->andReturn(100);
        $value->shouldReceive('getLabel')->andReturn("label");
        $value->shouldReceive('isHidden')->andReturn(false);

        $decorator = \Mockery::mock(ColorpickerMountPointPresenter::class);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            false,
            false,
            "/plugins/tracker/?tracker=5&func=admin-formElement-update&formElement=111&bind-update=1&bind%5Bdelete%5D=100",
            "You can't hide this value since it is used in a semantic, in workflow or in field dependency",
            'cannot hide',
            '--exclamation-hidden',
            false
        );

        $this->field->shouldReceive('getId')->andReturn(111);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, false);

        $this->assertEquals($expected_result, $result);
    }

    public function testBuildPresenterWithCustomValue(): void
    {
        $value = \Mockery::mock(Tracker_FormElement_Field_List_OpenValue::class);
        $value->shouldReceive('getId')->andReturn(100);
        $value->shouldReceive('getLabel')->andReturn("label");
        $value->shouldReceive('isHidden')->andReturn(false);

        $decorator = \Mockery::mock(ColorpickerMountPointPresenter::class);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            true,
            false,
            "/plugins/tracker/?tracker=5&func=admin-formElement-update&formElement=111&bind-update=1&bind%5Bdelete%5D=100",
            "Show/hide this value",
            'Show/hide this value',
            '',
            true
        );

        $this->field->shouldReceive('getId')->andReturn(111);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, true);

        $this->assertEquals($expected_result, $result);
    }
}
