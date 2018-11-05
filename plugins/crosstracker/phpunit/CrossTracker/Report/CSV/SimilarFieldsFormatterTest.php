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
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;

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

    protected function setUp()
    {
        parent::setUp();

        $this->similar_fields = Mockery::mock(SimilarFieldCollection::class);
        $this->parameters     = Mockery::mock(FormatterParameters::class);

        $this->csv_formatter_visitor = Mockery::mock(CSVFormatterVisitor::class);
        $this->formatter             = new SimilarFieldsFormatter(
            $this->csv_formatter_visitor
        );
    }

    public function testFormatSimilarFields()
    {
        $artifact       = Mockery::mock(\Tracker_Artifact::class);
        $last_changeset = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $artifact->shouldReceive(
            [
                'getLastChangeset' => $last_changeset
            ]
        );

        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class);
        $string_field->shouldReceive('accept')->passthru();

        $text_field = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $text_field->shouldReceive('accept')->passthru();

        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $float_field->shouldReceive('accept')->passthru();

        $date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $date_field->shouldReceive('accept')->passthru();
        $date_field->shouldReceive('isTimeDisplayed')->andReturn(true);

        $this->similar_fields->shouldReceive('getFieldNames')->andReturn(
            [
                'pentarchical',
                'semestrial',
                'overimaginative',
                'lithocenosis'
            ]
        );
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$artifact, 'pentarchical'])->andReturn($string_field);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$artifact, 'semestrial'])->andReturn($text_field);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$artifact, 'overimaginative'])->andReturn($float_field);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$artifact, 'lithocenosis'])->andReturn($date_field);

        $string_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $string_changeset_value->shouldReceive('getValue')->andReturn('safari');

        $text_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $text_changeset_value->shouldReceive('getValue')->andReturn('inappendiculate gas pearly');

        $float_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Float::class);
        $float_changeset_value->shouldReceive('getValue')->andReturn(48.6946);

        $date_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $date_changeset_value->shouldReceive('getTimestamp')->andReturn(1541436176);

        $last_changeset->shouldReceive('getValue')->withArgs([$string_field])->andReturn($string_changeset_value);
        $last_changeset->shouldReceive('getValue')->withArgs([$text_field])->andReturn($text_changeset_value);
        $last_changeset->shouldReceive('getValue')->withArgs([$float_field])->andReturn($float_changeset_value);
        $last_changeset->shouldReceive('getValue')->withArgs([$date_field])->andReturn($date_changeset_value);

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

        $result = $this->formatter->formatSimilarFields($artifact, $this->similar_fields, $this->parameters);

        $this->assertEquals([
            $formatted_string_field,
            $formatted_text_field,
            $formatted_float_field,
            $formatted_date_field
        ], $result);
    }
}
