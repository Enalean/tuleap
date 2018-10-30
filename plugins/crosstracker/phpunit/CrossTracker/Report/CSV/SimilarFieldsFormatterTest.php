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
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;

class SimilarFieldsFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery/MockInterface */
    private $visitor;
    /** @var Mockery\MockInterface */
    private $parameters;
    /** @var Mockery/MockInterface */
    private $similar_fields;
    /** @var SimilarFieldsFormatter */
    private $formatter;

    protected function setUp()
    {
        parent::setUp();

        $this->similar_fields = Mockery::mock(SimilarFieldCollection::class);
        $this->parameters     = Mockery::mock(FormatterParameters::class);

        $this->visitor   = Mockery::mock(CSVFormatterVisitor::class);
        $this->formatter = new SimilarFieldsFormatter($this->visitor);
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
        $this->similar_fields->shouldReceive('getFieldNames')->andReturn(['pentarchical']);
        $this->similar_fields->shouldReceive('getField')
            ->withArgs([$artifact, 'pentarchical'])->andReturn($string_field);

        $string_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $last_changeset->shouldReceive('getValue')->withArgs([$string_field])->andReturn($string_changeset_value);
        $string_changeset_value->shouldReceive('getValue')->andReturn('safari');

        $formatted_string_field = '"safari"';

        $this->visitor->shouldReceive('visitTextValue')->andReturn(
            $formatted_string_field
        );

        $result = $this->formatter->formatSimilarFields($artifact, $this->similar_fields, $this->parameters);

        $this->assertEquals([
            $formatted_string_field
        ], $result);
    }
}
