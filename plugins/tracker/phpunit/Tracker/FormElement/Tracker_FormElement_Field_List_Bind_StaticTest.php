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
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_List_Bind_StaticTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;

    protected function setUp(): void
    {
        parent::setUp();

        $first_value  = new Tracker_FormElement_Field_List_Bind_StaticValue(
            431,
            '10',
            'int value',
            1,
            0
        );

        $second_value  = new Tracker_FormElement_Field_List_Bind_StaticValue(
            432,
            '123abc',
            'string value',
            2,
            0
        );

        $field          = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $is_rank_alpha  = 0;
        $values         = [
            431 => $first_value,
            432 => $second_value
        ];

        $default_values = [];
        $decorators     = [];

        $this->bind = new Tracker_FormElement_Field_List_Bind_Static(
            $field,
            $is_rank_alpha,
            $values,
            $default_values,
            $decorators
        );
    }

    public function testItReturnsNumericValuesFromListInChangesetValue()
    {
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->shouldReceive('getValue')
            ->once()
            ->andReturn(['431']);

        $this->assertSame(
            ['10'],
            $this->bind->getNumericValues($changeset_value)
        );
    }

    public function testItReturnsAnEmptyArrayFromListInChangesetValueIfSelectedValueIsNotANumericValue()
    {
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value->shouldReceive('getValue')
            ->once()
            ->andReturn(['432']);

        $this->assertEmpty($this->bind->getNumericValues($changeset_value));
    }
}
