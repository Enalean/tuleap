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

namespace Tuleap\CrossTracker\Report\SimilarField;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FieldUsedInSupportedSemanticsVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var FieldUsedInSupportedSemanticsVisitor */
    private $visitor;
    /** @var \Tracker_Semantic_Title | Mockery\MockInterface */
    private $title_semantic;
    /** @var \Tracker_Semantic_Description | Mockery\MockInterface */
    private $description_semantic;
    /** @var \Tracker_Semantic_Status | Mockery\MockInterface */
    private $status_semantic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->title_semantic       = Mockery::mock(\Tracker_Semantic_Title::class);
        $this->description_semantic = Mockery::mock(\Tracker_Semantic_Description::class);
        $this->status_semantic      = Mockery::mock(\Tracker_Semantic_Status::class);
        $this->visitor              = new FieldUsedInSupportedSemanticsVisitor(
            $this->title_semantic,
            $this->description_semantic,
            $this->status_semantic
        );
    }

    public function testVisitString()
    {
        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class);
        $this->title_semantic->shouldReceive('isUsedInSemantics')->with($string_field)->andReturns(true);

        $this->assertTrue($this->visitor->visitString($string_field));
    }

    public function testItReturnsTrueWhenTextFieldIsUsedInTitle()
    {
        $text_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->title_semantic->shouldReceive('isUsedInSemantics')->with($text_field)->andReturns(true);

        $this->assertTrue($this->visitor->visitText($text_field));
    }

    public function testItReturnsTrueWhenTextFieldIsUsedInDescription()
    {
        $text_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->title_semantic->shouldReceive('isUsedInSemantics')->with($text_field)->andReturns(false);
        $this->description_semantic->shouldReceive('isUsedInSemantics')->with($text_field)->andReturns(true);

        $this->assertTrue($this->visitor->visitText($text_field));
    }

    public function testVisitSelectbox()
    {
        $selectbox_field = Mockery::mock(\Tracker_FormElement_Field_Selectbox::class);
        $this->status_semantic->shouldReceive('isUsedInSemantics')->with($selectbox_field)->andReturns(true);

        $this->assertTrue($this->visitor->visitSelectbox($selectbox_field));
    }

    public function testVisitRadiobutton()
    {
        $radio_button = Mockery::mock(\Tracker_FormElement_Field_Radiobutton::class);
        $this->status_semantic->shouldReceive('isUsedInSemantics')->with($radio_button)->andReturns(true);

        $this->assertTrue($this->visitor->visitSelectbox($radio_button));
    }

    public function testVisitDate()
    {
        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->assertFalse($this->visitor->visitDate($date_field));
    }

    public function testVisitInteger()
    {
        $integer_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class);

        $this->assertFalse($this->visitor->visitInteger($integer_field));
    }

    public function testVisitFloat()
    {
        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);

        $this->assertFalse($this->visitor->visitFloat($float_field));
    }
}
