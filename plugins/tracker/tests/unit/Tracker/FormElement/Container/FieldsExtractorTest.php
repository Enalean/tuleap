<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Container;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Container;
use Tracker_FormElement_Container_Column;
use Tracker_FormElement_Field;

require_once __DIR__ . '/../../../bootstrap.php';

class FieldsExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldsExtractor
     */
    private $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new FieldsExtractor();
    }

    public function testItExtractsFieldsDirectlyInsideTheContainer()
    {
        $field_01 = Mockery::mock(Tracker_FormElement_Field::class);
        $field_02 = Mockery::mock(Tracker_FormElement_Field::class);

        $container = Mockery::mock(Tracker_FormElement_Container::class);
        $container->shouldReceive('getFormElements')->andReturn([$field_01, $field_02]);

        $this->assertSame(
            [$field_01, $field_02],
            $this->extractor->extractFieldsInsideContainer($container)
        );
    }

    public function testItExtractsFieldsDirectlyInsideTheContainerAndInsideContainerIntoContainer()
    {
        $field_01 = Mockery::mock(Tracker_FormElement_Field::class);
        $field_02 = Mockery::mock(Tracker_FormElement_Field::class);
        $field_03 = Mockery::mock(Tracker_FormElement_Field::class);

        $column_01 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_02 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_03 = Mockery::mock(Tracker_FormElement_Container_Column::class);

        $column_01->shouldReceive('getFormElements')->andReturn([$column_02, $column_03]);
        $column_02->shouldReceive('getFormElements')->andReturn([$field_02]);
        $column_03->shouldReceive('getFormElements')->andReturn([$field_03]);

        $container = Mockery::mock(Tracker_FormElement_Container::class);
        $container->shouldReceive('getFormElements')->andReturn([$field_01, $column_01]);

        $this->assertSame(
            [$field_01, $field_02, $field_03],
            $this->extractor->extractFieldsInsideContainer($container)
        );
    }
}
