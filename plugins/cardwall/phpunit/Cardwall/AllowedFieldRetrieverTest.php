<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;

class AllowedFieldRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $field;
    /**
     * @var Tracker_FormElementFactory
     */
    private $checker;
    /**
     * @var AllowedFieldRetriever
     */
    private $allowed_field_retriever;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->checker                 = Mockery::spy(FieldUsedInSemanticObjectChecker::class);
        $this->form_element_factory    = Mockery::spy(Tracker_FormElementFactory::class);
        $this->allowed_field_retriever = new AllowedFieldRetriever($this->form_element_factory, $this->checker);

        $this->field = Mockery::spy(\Tracker_FormElement_Field::class);
    }

    public function testReturnsAndEmptyArrayIfFieldIsNotUsedForBackgroundSemantic()
    {
        $this->checker->shouldReceive('isUsedInBackgroundColorSemantic')->andReturn(false);
        $expected_allowed_fields = [];
        $allowed_fields          = $this->allowed_field_retriever->retrieveAllowedFieldType($this->field);

        $this->assertEquals($expected_allowed_fields, $allowed_fields);
    }

    public function testItOnlyAllowsRbWhenFieldIsASb()
    {
        $this->checker->shouldReceive('isUsedInBackgroundColorSemantic')->andReturn(true);
        $this->form_element_factory->shouldReceive('getType')->andReturn('sb');
        $expected_allowed_fields = ['rb'];
        $allowed_fields          = $this->allowed_field_retriever->retrieveAllowedFieldType($this->field);

        $this->assertEquals($expected_allowed_fields, $allowed_fields);
    }

    public function testItOnlyAllowsSbWhenFieldIsARb()
    {
        $this->checker->shouldReceive('isUsedInBackgroundColorSemantic')->andReturn(true);
        $this->form_element_factory->shouldReceive('getType')->andReturn('rb');
        $expected_allowed_fields = ['sb'];
        $allowed_fields          = $this->allowed_field_retriever->retrieveAllowedFieldType($this->field);

        $this->assertEquals($expected_allowed_fields, $allowed_fields);
    }

    public function testReturnsAndEmptyArrayByDefault()
    {
        $this->checker->shouldReceive('isUsedInBackgroundColorSemantic')->andReturn(true);
        $this->form_element_factory->shouldReceive('getType')->andReturn('msb');
        $expected_allowed_fields = [];
        $allowed_fields          = $this->allowed_field_retriever->retrieveAllowedFieldType($this->field);

        $this->assertEquals($expected_allowed_fields, $allowed_fields);
    }
}
