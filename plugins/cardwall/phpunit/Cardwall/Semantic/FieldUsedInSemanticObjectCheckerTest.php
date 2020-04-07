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

namespace Tuleap\Cardwall\Semantic;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FieldUsedInSemanticObjectCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $field;

    /**
     * @var FieldUsedInSemanticObjectChecker
     */
    private $checker;
    /**
     * @var BackgroundColorDao
     */
    private $background_dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->background_dao = \Mockery::mock(BackgroundColorDao::class);
        $this->checker        = new FieldUsedInSemanticObjectChecker($this->background_dao);

        $this->field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $this->field->shouldReceive('getId')->andReturn(101);
    }

    public function testItShouldReturnTrueIfFieldIsUsedInCardFieldSemantic()
    {
        $card_field1 = \Mockery::mock(\Tracker_FormElement_Field::class);
        $card_field1->shouldReceive('getId')->andReturn(100);

        $card_field2 = \Mockery::mock(\Tracker_FormElement_Field::class);
        $card_field2->shouldReceive('getId')->andReturn(101);

        $card_fields = [$card_field1, $card_field2];

        $this->assertTrue($this->checker->isUsedInSemantic($this->field, $card_fields));
    }

    public function testItShouldReturnTrueIfFieldIsUsedInBackgroundColorSemantic()
    {
        $card_fields = [];

        $this->background_dao->shouldReceive('isFieldUsedAsBackgroundColor')->andReturn(101);

        $this->assertTrue($this->checker->isUsedInSemantic($this->field, $card_fields));
    }

    public function testItShouldShouldReturnFalseWhenFieldIsNotACardFieldAndNotABAckgroundColorField()
    {
        $card_field1 = \Mockery::mock(\Tracker_FormElement_Field::class);
        $card_field1->shouldReceive('getId')->andReturn(104);

        $card_field2 = \Mockery::mock(\Tracker_FormElement_Field::class);
        $card_field2->shouldReceive('getId')->andReturn(105);

        $this->background_dao->shouldReceive('isFieldUsedAsBackgroundColor')->andReturn(false);

        $card_fields = [$card_field1, $card_field2];

        $this->assertFalse($this->checker->isUsedInSemantic($this->field, $card_fields));
    }
}
