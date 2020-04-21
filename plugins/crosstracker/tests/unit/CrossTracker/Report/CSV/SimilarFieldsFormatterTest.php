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

namespace Tuleap\CrossTracker\Report\CSV;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_ChangesetValue;
use Tuleap\CrossTracker\Report\CSV\Format\BindToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldIdentifier;

class SimilarFieldsFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface | CSVFormatterVisitor */
    private $csv_formatter_visitor;
    /** @var Mockery\MockInterface | FormatterParameters */
    private $parameters;
    /** @var Mockery\MockInterface | SimilarFieldCollection */
    private $similar_fields;
    /** @var SimilarFieldsFormatter */
    private $formatter;
    /** @var Mockery\MockInterface | BindToValueVisitor */
    private $bind_to_value_visitor;
    /** @var Mockery\MockInterface | \Tracker_Artifact */
    private $artifact;
    /** @var Mockery\MockInterface | \Tracker_Artifact_Changeset */
    private $last_changeset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->similar_fields = Mockery::mock(SimilarFieldCollection::class);
        $this->parameters     = Mockery::mock(FormatterParameters::class);

        $this->csv_formatter_visitor = Mockery::mock(CSVFormatterVisitor::class);
        $this->bind_to_value_visitor = Mockery::mock(BindToValueVisitor::class);
        $this->formatter             = new SimilarFieldsFormatter(
            $this->csv_formatter_visitor,
            $this->bind_to_value_visitor
        );
        $this->artifact              = Mockery::mock(\Tracker_Artifact::class);
        $this->last_changeset        = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive(
            [
                'getLastChangeset' => $this->last_changeset
            ]
        );
    }

    public function testFormatSimilarFields()
    {
        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('accept')->passthru();
        $string_field_identifier = new SimilarFieldIdentifier('pentarchical', null);

        $text_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $text_field->shouldReceive('accept')->passthru();
        $text_field_identifier = new SimilarFieldIdentifier('semestrial', null);

        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $float_field->shouldReceive('accept')->passthru();
        $float_field_identifier = new SimilarFieldIdentifier('overimaginative', null);

        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $date_field->shouldReceive('accept')->passthru();
        $date_field->shouldReceive('isTimeDisplayed')->andReturn(true);
        $date_field_identifier = new SimilarFieldIdentifier('lithocenosis', null);

        $this->similar_fields->shouldReceive('getFieldIdentifiers')->andReturn(
            [
                $string_field_identifier,
                $text_field_identifier,
                $float_field_identifier,
                $date_field_identifier,
            ]
        );
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$this->artifact, $string_field_identifier])->andReturn($string_field);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$this->artifact, $text_field_identifier])->andReturn($text_field);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$this->artifact, $float_field_identifier])->andReturn($float_field);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$this->artifact, $date_field_identifier])->andReturn($date_field);

        $string_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $string_changeset_value->shouldReceive('getValue')->andReturn('safari');

        $text_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $text_changeset_value->shouldReceive('getContentAsText')->andReturn('inappendiculate gas pearly');

        $float_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Float::class);
        $float_changeset_value->shouldReceive('getValue')->andReturn(48.6946);

        $date_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $date_changeset_value->shouldReceive('getTimestamp')->andReturn(1541436176);

        $this->last_changeset->shouldReceive('getValue')->withArgs([$string_field])->andReturn($string_changeset_value);
        $this->last_changeset->shouldReceive('getValue')->withArgs([$text_field])->andReturn($text_changeset_value);
        $this->last_changeset->shouldReceive('getValue')->withArgs([$float_field])->andReturn($float_changeset_value);
        $this->last_changeset->shouldReceive('getValue')->withArgs([$date_field])->andReturn($date_changeset_value);

        $formatted_string_field = '"safari"';
        $formatted_text_field   = '"inappendiculate gas pearly"';
        $formatted_float_field = 48.6946;
        $formatted_date_field = '05/11/2018 17:42';

        $this->csv_formatter_visitor->shouldReceive('visitTextValue')->andReturn(
            $formatted_string_field,
            $formatted_text_field
        );
        $this->csv_formatter_visitor->shouldReceive('visitNumericValue')->andReturn($formatted_float_field);
        $this->csv_formatter_visitor->shouldReceive('visitDateValue')->andReturn($formatted_date_field);

        $result = $this->formatter->formatSimilarFields($this->artifact, $this->similar_fields, $this->parameters);

        $this->assertEquals([
            $formatted_string_field,
            $formatted_text_field,
            $formatted_float_field,
            $formatted_date_field
        ], $result);
    }

    public function testItReturnsEmptyValueWhenTheFieldIsNotPresent()
    {
        $field_identifier = new SimilarFieldIdentifier('didactics', 'static');
        $this->similar_fields->shouldReceive('getFieldIdentifiers')->andReturn([$field_identifier]);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$this->artifact, $field_identifier])->andReturn(null);

        $result = $this->formatter->formatSimilarFields($this->artifact, $this->similar_fields, $this->parameters);

        $this->assertEquals([''], $result);
    }

    public function testItReturnsEmptyValueWhenTheFieldHasNoValue()
    {
        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $float_field->shouldNotReceive('accept');
        $float_field_identifier = new SimilarFieldIdentifier('isonomous', null);

        $this->last_changeset->shouldReceive('getValue')
            ->with($float_field)->andReturn(null);

        $this->similar_fields->shouldReceive('getFieldIdentifiers')->andReturn([$float_field_identifier]);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$this->artifact, $float_field_identifier])->andReturn($float_field);

        $result = $this->formatter->formatSimilarFields($this->artifact, $this->similar_fields, $this->parameters);

        $this->assertEquals([''], $result);
    }
}
