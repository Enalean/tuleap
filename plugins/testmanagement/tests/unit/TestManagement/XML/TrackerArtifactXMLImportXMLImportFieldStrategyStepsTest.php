<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use SimpleXMLElement;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

final class TrackerArtifactXMLImportXMLImportFieldStrategyStepsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetFieldData(): void
    {
        $xml_data = '
            <external_field_change field_name="steps" type="ttmstepdef">
		    <step>
		        <description format="text"><![CDATA[Yep]]></description>
		        <expected_results format="text"><![CDATA[Non]]></expected_results>
		    </step>
		    <step>
		        <description format="html"><![CDATA[Yep]]></description>
		        <expected_results format="html"><![CDATA[Non]]></expected_results>
            </step>
	        </external_field_change>';

        $xml = new SimpleXMLElement($xml_data);

        $import_strategie = new TrackerArtifactXMLImportXMLImportFieldStrategySteps();
        $field            = Mockery::mock(Tracker_FormElement_Field::class);
        $user             = Mockery::mock(PFUser::class);
        $artifact         = Mockery::mock(Artifact::class);

        $data = [
            'description_format'      => ["text", "html"],
            'description'             => ["Yep", "Yep"],
            'expected_results_format' => ["text", "html"],
            'expected_results'        => ["Non", "Non"],
        ];

        $this->assertEquals($data, $import_strategie->getFieldData($field, $xml, $user, $artifact, PostCreationContext::withNoConfig(false)));
    }
}
