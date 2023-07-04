<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

final class FieldUsedInSupportedSemanticsVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FieldUsedInSupportedSemanticsVisitor $visitor;
    /** @var \Tracker_Semantic_Title&\PHPUnit\Framework\MockObject\MockObject */
    private $title_semantic;
    /** @var \Tracker_Semantic_Description&\PHPUnit\Framework\MockObject\MockObject */
    private $description_semantic;
    /** @var \Tracker_Semantic_Status&\PHPUnit\Framework\MockObject\MockObject */
    private $status_semantic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->title_semantic       = $this->createMock(\Tracker_Semantic_Title::class);
        $this->description_semantic = $this->createMock(\Tracker_Semantic_Description::class);
        $this->status_semantic      = $this->createMock(\Tracker_Semantic_Status::class);
        $this->visitor              = new FieldUsedInSupportedSemanticsVisitor(
            $this->title_semantic,
            $this->description_semantic,
            $this->status_semantic
        );
    }

    public function testVisitString(): void
    {
        $string_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $this->title_semantic->method('isUsedInSemantics')->with($string_field)->willReturn(true);

        $this->assertTrue($this->visitor->visitString($string_field));
    }

    public function testItReturnsTrueWhenTextFieldIsUsedInTitle(): void
    {
        $text_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->title_semantic->method('isUsedInSemantics')->with($text_field)->willReturn(true);

        $this->assertTrue($this->visitor->visitText($text_field));
    }

    public function testItReturnsTrueWhenTextFieldIsUsedInDescription(): void
    {
        $text_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->title_semantic->method('isUsedInSemantics')->with($text_field)->willReturn(false);
        $this->description_semantic->method('isUsedInSemantics')->with($text_field)->willReturn(true);

        $this->assertTrue($this->visitor->visitText($text_field));
    }

    public function testVisitSelectbox(): void
    {
        $selectbox_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->status_semantic->method('isUsedInSemantics')->with($selectbox_field)->willReturn(true);

        $this->assertTrue($this->visitor->visitSelectbox($selectbox_field));
    }

    public function testVisitRadiobutton(): void
    {
        $radio_button = $this->createMock(\Tracker_FormElement_Field_Radiobutton::class);
        $this->status_semantic->method('isUsedInSemantics')->with($radio_button)->willReturn(true);

        $this->assertTrue($this->visitor->visitSelectbox($radio_button));
    }

    public function testVisitDate(): void
    {
        $date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);

        $this->assertFalse($this->visitor->visitDate($date_field));
    }

    public function testVisitInteger(): void
    {
        $integer_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);

        $this->assertFalse($this->visitor->visitInteger($integer_field));
    }

    public function testVisitFloat(): void
    {
        $float_field = $this->createMock(\Tracker_FormElement_Field_Float::class);

        $this->assertFalse($this->visitor->visitFloat($float_field));
    }
}
