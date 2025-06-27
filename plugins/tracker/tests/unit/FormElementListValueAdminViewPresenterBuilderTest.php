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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenter;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenterBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\OpenListValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FormElementListValueAdminViewPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FormElementListValueAdminViewPresenterBuilder $presenter_builder;
    private BindStaticValueDao&MockObject $value_dao;
    private Tracker_FormElement_Field&MockObject $field;

    protected function setUp(): void
    {
        $this->field = $this->createMock(Tracker_FormElement_Field::class);
        $this->field->method('getTrackerId')->willReturn(5);

        $this->value_dao         = $this->createMock(BindStaticValueDao::class);
        $this->presenter_builder = new FormElementListValueAdminViewPresenterBuilder($this->value_dao);
    }

    public function testBuildPresenter(): void
    {
        $value = ListStaticValueBuilder::aStaticValue('label')->withId(666)->withDescription('description')->build();

        $decorator = new ColorpickerMountPointPresenter('fiesta-red', 'name', 'id', true, false);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            true,
            false,
            false
        );

        $this->field->method('getId')->willReturn(111);

        $this->value_dao->method('canValueBeHidden')->willReturn(true);
        $this->value_dao->method('canValueBeDeleted')->willReturn(false);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, false);

        $this->assertEquals($expected_result, $result);
    }

    public function testBuildPresenterNoneValueCantBeDeletedOrId(): void
    {
        $value     = ListStaticValueBuilder::noneStaticValue()->build();
        $decorator = new ColorpickerMountPointPresenter('fiesta-red', 'name', 'id', true, false);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            false,
            false,
            false
        );

        $this->field->method('getId')->willReturn(111);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, false);

        $this->assertEquals($expected_result, $result);
    }

    public function testBuildPresenterWithCustomValue(): void
    {
        $value = OpenListValueBuilder::anOpenListValue('label')->withId(123)->build();

        $decorator = new ColorpickerMountPointPresenter('fiesta-red', 'name', 'id', true, false);

        $expected_result = new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            true,
            false,
            true
        );

        $this->field->method('getId')->willReturn(111);

        $result = $this->presenter_builder->buildPresenter($this->field, $value, $decorator, true);

        $this->assertEquals($expected_result, $result);
    }
}
