<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV;

use Tracker_Artifact_ChangesetValue;
use Tuleap\CrossTracker\Report\CSV\Format\BindToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldIdentifier;
use Tuleap\Tracker\Artifact\Artifact;

class SimilarFieldsFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&CSVFormatterVisitor */
    private $csv_formatter_visitor;
    /** @var \PHPUnit\Framework\MockObject\MockObject&FormatterParameters */
    private $parameters;
    /** @var \PHPUnit\Framework\MockObject\MockObject&SimilarFieldCollection */
    private $similar_fields;
    private SimilarFieldsFormatter $formatter;
    /** @var \PHPUnit\Framework\MockObject\MockObject&BindToValueVisitor */
    private $bind_to_value_visitor;
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact */
    private $artifact;
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_Artifact_Changeset */
    private $last_changeset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->similar_fields = $this->createMock(SimilarFieldCollection::class);
        $this->parameters     = $this->createMock(FormatterParameters::class);

        $this->csv_formatter_visitor = $this->createMock(CSVFormatterVisitor::class);
        $this->bind_to_value_visitor = $this->createMock(BindToValueVisitor::class);
        $this->formatter             = new SimilarFieldsFormatter(
            $this->csv_formatter_visitor,
            $this->bind_to_value_visitor
        );
        $this->artifact              = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->last_changeset        = $this->createMock(\Tracker_Artifact_Changeset::class);
        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
    }

    public function testFormatSimilarFields(): void
    {
        $string_field = $this->getMockBuilder(\Tracker_FormElement_Field_String::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $string_field_identifier = new SimilarFieldIdentifier('pentarchical', null);

        $text_field = $this->getMockBuilder(\Tracker_FormElement_Field_Text::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $text_field_identifier = new SimilarFieldIdentifier('semestrial', null);

        $float_field = $this->getMockBuilder(\Tracker_FormElement_Field_Float::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $float_field_identifier = new SimilarFieldIdentifier('overimaginative', null);

        $date_field = $this->getMockBuilder(\Tracker_FormElement_Field_Date::class)
            ->onlyMethods(['isTimeDisplayed'])
            ->disableOriginalConstructor()
            ->getMock();

        $date_field->method('isTimeDisplayed')->willReturn(true);
        $date_field_identifier = new SimilarFieldIdentifier('lithocenosis', null);

        $this->similar_fields->method('getFieldIdentifiers')->willReturn(
            [
                $string_field_identifier,
                $text_field_identifier,
                $float_field_identifier,
                $date_field_identifier,
            ]
        );

        $this->similar_fields->method('getField')->willReturnMap([
            [$this->artifact, $string_field_identifier, $string_field],
            [$this->artifact, $text_field_identifier, $text_field],
            [$this->artifact, $float_field_identifier, $float_field],
            [$this->artifact, $date_field_identifier, $date_field],
        ]);

        $string_changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $string_changeset_value->method('getValue')->willReturn('safari');

        $text_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $text_changeset_value->method('getContentAsText')->willReturn('inappendiculate gas pearly');

        $float_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Float::class);
        $float_changeset_value->method('getValue')->willReturn(48.6946);

        $date_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Date::class);
        $date_changeset_value->method('getTimestamp')->willReturn(1541436176);

        $this->last_changeset->method('getValue')->willReturnMap([
            [$string_field, $string_changeset_value],
            [$text_field, $text_changeset_value],
            [$float_field, $float_changeset_value],
            [$date_field, $date_changeset_value],
        ]);

        $formatted_string_field = '"safari"';
        $formatted_text_field   = '"inappendiculate gas pearly"';
        $formatted_float_field  = 48.6946;
        $formatted_date_field   = '05/11/2018 17:42';

        $this->csv_formatter_visitor->method('visitTextValue')->willReturnOnConsecutiveCalls(
            $formatted_string_field,
            $formatted_text_field
        );
        $this->csv_formatter_visitor->method('visitNumericValue')->willReturn($formatted_float_field);
        $this->csv_formatter_visitor->method('visitDateValue')->willReturn($formatted_date_field);

        $result = $this->formatter->formatSimilarFields($this->artifact, $this->similar_fields, $this->parameters);

        self::assertEqualsCanonicalizing(
            [
                $formatted_string_field,
                $formatted_text_field,
                $formatted_float_field,
                $formatted_date_field,
            ],
            $result,
        );
    }

    public function testItReturnsEmptyArrayWhenTheArtifactHasNoLastChangeset(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn(null);
        $field_identifier = new SimilarFieldIdentifier('whicker', 'static');
        $this->similar_fields->method('getFieldIdentifiers')->willReturn([$field_identifier]);

        $result = $this->formatter->formatSimilarFields($artifact, $this->similar_fields, $this->parameters);

        self::assertEquals([], $result);
    }

    public function testItReturnsEmptyValueWhenTheFieldIsNotPresent(): void
    {
        $field_identifier = new SimilarFieldIdentifier('didactics', 'static');
        $this->similar_fields->method('getFieldIdentifiers')->willReturn([$field_identifier]);
        $this->similar_fields->method('getField')->with($this->artifact, $field_identifier)->willReturn(null);

        $result = $this->formatter->formatSimilarFields($this->artifact, $this->similar_fields, $this->parameters);

        self::assertEquals([''], $result);
    }

    public function testItReturnsEmptyValueWhenTheFieldHasNoValue(): void
    {
        $float_field = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $float_field->expects(self::never())->method('accept');
        $float_field_identifier = new SimilarFieldIdentifier('isonomous', null);

        $this->last_changeset->method('getValue')->with($float_field)->willReturn(null);

        $this->similar_fields->method('getFieldIdentifiers')->willReturn([$float_field_identifier]);
        $this->similar_fields->method('getField')->with($this->artifact, $float_field_identifier)->willReturn($float_field);

        $result = $this->formatter->formatSimilarFields($this->artifact, $this->similar_fields, $this->parameters);

        self::assertEquals([''], $result);
    }
}
