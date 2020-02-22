<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_String;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class TrackerFormElementJsonTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_String
     */
    private $form_element;

    protected function setUp(): void
    {
        $this->form_element = Mockery::mock(Tracker_FormElement_Field_String::class)->makePartial();
        $this->form_element->setId(300);
        $this->form_element->shouldReceive('getLabel')->andReturn("My field");
        $this->form_element->shouldReceive('getName')->andReturn('my_field');
    }

    public function testItHasAllFieldElementsInJsonReadyArray()
    {
        $this->assertEquals(
            [
                'id'    => 300,
                'label' => 'My field',
                'name'  => 'my_field',
            ],
            $this->form_element->fetchFormattedForJson()
        );
    }
}
