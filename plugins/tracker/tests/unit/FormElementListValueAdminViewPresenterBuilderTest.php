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

declare(strict_types=1);

namespace Tuleap\Tracker;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_FormElement_Field;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenter;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenterBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FormElementListValueAdminViewPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FormElementListValueAdminViewPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \Mockery\MockInterface|BindStaticValueDao
     */
    private $value_dao;
    /**
     * @var \Mockery\MockInterface|Tracker_FormElement_Field
     */
    private $field;

    protected function setUp(): void
    {
        $this->field = \Mockery::mock(Tracker_FormElement_Field::class);
        $this->field->shouldReceive('getTrackerId')->andReturn(5);

        $this->value_dao         = \Mockery::mock(BindStaticValueDao::class);
        $this->presenter_builder = new FormElementListValueAdminViewPresenterBuilder($this->value_dao);
    }

    public function testBuildPresenter(): void
    {
        $value = \Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value->shouldReceive('getId')->andReturn(666);
        $value->shouldReceive('getLabel')->andReturn('label');
        $value->shouldReceive('getDescription')->andReturn('description');
        $value->shouldReceive('isHidden')->andReturn(false);

        $decorator = new ColorpickerMountPointPresenter('fiesta-red', 'name', 'id', true, false);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            true,
            false,
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
        $value->shouldReceive('getLabel')->andReturn('label');
        $value->shouldReceive('getDescription')->andReturn('description');

        $value->shouldReceive('isHidden')->andReturn(false);

        $decorator = new ColorpickerMountPointPresenter('fiesta-red', 'name', 'id', true, false);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            false,
            false,
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
        $value->shouldReceive('getLabel')->andReturn('label');
        $value->shouldReceive('isHidden')->andReturn(false);

        $decorator = new ColorpickerMountPointPresenter('fiesta-red', 'name', 'id', true, false);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            true,
            false,
            true
        );

        $this->field->shouldReceive('getId')->andReturn(111);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, true);

        $this->assertEquals($expected_result, $result);
    }
}
