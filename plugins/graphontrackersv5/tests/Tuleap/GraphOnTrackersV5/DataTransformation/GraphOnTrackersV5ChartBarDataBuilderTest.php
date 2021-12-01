<?php
/**
 * Copyright (c) Enalean 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\GraphOnTrackersV5\DataTransformation;

use GraphOnTrackersV5_Chart_BarDataBuilder;
use GraphOnTrackersV5_Engine_Bar;
use PFUser;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;

final class GraphOnTrackersV5ChartBarDataBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GraphOnTrackersV5_Chart_BarDataBuilder
     */
    private $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElement_Field_List
     */
    private $source_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElement_Field_List
     */
    private $group_field;

    protected function setUp(): void
    {
        $this->builder = $this->createPartialMock(
            GraphOnTrackersV5_Chart_BarDataBuilder::class,
            ['buildParentProperties', 'buildSourceField', 'getFieldGroupId', 'getFieldBaseId', 'getArtifactIds', 'getArtifactsLastChangesetIds', 'getQueryResult', 'getFormElementFactory']
        );
        $this->factory = $this->createMock(Tracker_FormElementFactory::class);
    }

    public function testItBuildsAnEngineForBarChart(): void
    {
        $engine = new GraphOnTrackersV5_Engine_Bar();
        $this->builder->expects($this->once())->method('buildParentProperties');
        $this->builder->expects($this->once())->method('buildSourceField')->will($this->returnValue($this->buildSourceField()));

        $this->builder->expects($this->once())->method('getFieldGroupId')->will($this->returnValue(10));
        $this->builder->expects($this->once())->method('getFieldBaseId')->will($this->returnValue(10));

        $this->builder->expects($this->atLeast(2))->method('getArtifactIds')->will($this->returnValue("1,2,3"));
        $this->builder->expects($this->once())->method('getArtifactsLastChangesetIds')->will($this->returnValue("100,200,300"));

        $this->builder->expects($this->once())->method('getQueryResult')->will($this->returnValue([
            ['nb' => 10, 'source_field' => 130, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 5, 'source_field' => 131, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 3, 'source_field' => 130, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
        ]));

        $this->builder->buildProperties($engine);

        $expected_data = [10, 5, 3];
        self::assertEquals($expected_data, $engine->data);
    }

    public function testItBuildAnEngineForGroupedBarChart(): void
    {
        $engine = new GraphOnTrackersV5_Engine_Bar();
        $this->builder->expects($this->once())->method('buildParentProperties');
        $source_field = $this->buildSourceField();

        $this->builder->expects($this->once())->method('buildSourceField')->will($this->returnValue($source_field));
        $this->builder->expects($this->once())->method('getFormElementFactory')->will($this->returnValue($this->factory));

        $group_field = $this->buildGroupField();
        $this->factory->expects($this->once())->method('getFormElementById')->will($this->returnValue($group_field));

        $this->builder->expects($this->atLeast(2))->method('getFieldGroupId')->will($this->returnValue(10));
        $this->builder->expects($this->once())->method('getFieldBaseId')->will($this->returnValue(20));
        $this->builder->expects($this->atLeast(2))->method('getArtifactIds')->will($this->returnValue("1,2,3"));
        $this->builder->expects($this->once())->method('getArtifactsLastChangesetIds')->will($this->returnValue("100,200,300"));

        $this->builder->expects($this->once())->method('getQueryResult')->will($this->returnValue([
            ['nb' => 10, 'source_field' => 130, 'group_field' => null, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 5, 'source_field' => 131, 'group_field' => 431, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
            ['nb' => 3, 'source_field' => 130, 'group_field' => 432, 'red' => null, 'green' => null, 'blue' => null, 'tlp_color_name' => null],
        ]));

        $this->builder->buildProperties($engine);

        $expected_data  = [130 => ['' => 10, 432 => 3], 131 => [431 => 5]];
        $expected_xaxis = [431 => "Abc", 432 => "Def", '' => null];
        self::assertEquals($expected_data, $engine->data);
        self::assertEquals($expected_xaxis, $engine->xaxis);
    }

    private static function buildGroupField(): Tracker_FormElement_Field_Selectbox
    {
        return new class (
            98,
            null,
            null,
            'group_field',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
) extends Tracker_FormElement_Field_Selectbox {
            public function accept(Tracker_FormElement_FieldVisitor $visitor)
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryLabel()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryDescription()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryIconUseIt()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryIconCreate()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function isNone($value)
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function userCanRead(?PFUser $user = null)
            {
                return true;
            }

            public function getBind()
            {
                $first_value = new Tracker_FormElement_Field_List_Bind_StaticValue(
                    431,
                    'Abc',
                    'Abc',
                    1,
                    0
                );

                $second_value = new Tracker_FormElement_Field_List_Bind_StaticValue(
                    432,
                    'Def',
                    'Def',
                    2,
                    0
                );

                return new \Tracker_FormElement_Field_List_Bind_Static(
                    $this,
                    false,
                    [431 => $first_value, 432 => $second_value],
                    [],
                    []
                );
            }
        };
    }

    private static function buildSourceField(): Tracker_FormElement_Field_Selectbox
    {
        return new class (
            100,
            null,
            null,
            'source_field',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
) extends Tracker_FormElement_Field_Selectbox {

            public function accept(Tracker_FormElement_FieldVisitor $visitor)
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryLabel()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryDescription()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryIconUseIt()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function getFactoryIconCreate()
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function isNone($value)
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function userCanRead(?PFUser $user = null)
            {
                return true;
            }

            public function getBind()
            {
                $first_value = new Tracker_FormElement_Field_List_Bind_StaticValue(
                    130,
                    '123',
                    '123',
                    1,
                    0
                );

                $second_value = new Tracker_FormElement_Field_List_Bind_StaticValue(
                    131,
                    '456',
                    '456',
                    2,
                    0
                );

                return new \Tracker_FormElement_Field_List_Bind_Static(
                    $this,
                    false,
                    [130 => $first_value, 131 => $second_value],
                    [],
                    []
                );
            }
        };
    }
}
