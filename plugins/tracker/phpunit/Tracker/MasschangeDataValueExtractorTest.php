<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tracker_MasschangeDataValueExtractor;
use Tuleap\GlobalLanguageMock;

final class MasschangeDataValueExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testItReturnsFieldWithItNewValue(): void
    {
        $form_element_factory           = Mockery::mock(Tracker_FormElementFactory::class);
        $masschange_data_values_manager = new Tracker_MasschangeDataValueExtractor($form_element_factory);

        $text_field_1    = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $text_field_1_id = 1;

        $text_field_2    = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $text_field_2_id = 2;

        $list_field_1    = Mockery::mock(Tracker_FormElement_Field_List::class);
        $list_field_1_id = 3;

        $list_field_2    = Mockery::mock(Tracker_FormElement_Field_List::class);
        $list_field_2_id = 4;

        $form_element_factory->shouldReceive('getFieldById')->withArgs([$text_field_1_id])->andReturn($text_field_1);
        $form_element_factory->shouldReceive('getFieldById')->withArgs([$text_field_2_id])->andReturn($text_field_2);
        $form_element_factory->shouldReceive('getFieldById')->withArgs([$list_field_1_id])->andReturn($list_field_1);
        $form_element_factory->shouldReceive('getFieldById')->withArgs([$list_field_2_id])->andReturn($list_field_2);

        $GLOBALS['Language']->shouldReceive('getText')->andReturn('Unchanged');

        $masschange_data = [
            $text_field_1_id => 'Unchanged',
            $text_field_2_id => 'Value01',
            $list_field_1_id => ['-1'],
            $list_field_2_id => ['Value02'],
        ];

        $expected_result = [
            $text_field_2_id => 'Value01',
            $list_field_2_id => ['Value02']
        ];

        $this->assertEquals(
            $expected_result,
            $masschange_data_values_manager->getNewValues($masschange_data)
        );
    }
}
