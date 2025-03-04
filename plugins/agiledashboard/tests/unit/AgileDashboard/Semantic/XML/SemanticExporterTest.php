<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Semantic\XML;

use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCatchWhenProvidedXMLIsInvalid(): void
    {
        $add = new SemanticsExporter();

        $this->expectException(\LogicException::class);

        $add->process(
            new \SimpleXMLElement('<tracker />'),
            new PlatformConfiguration(),
            new FieldMappingCollection()
        );
    }

    public function testItDoesNotSetTheSemanticWhenPlatformDoesNotHaveStoryPointField(): void
    {
        $jira_story_points_field_id    = 'customfield_10004';
        $jira_story_points_field_label = 'Story points';
        $xml_story_points_id           = 'F123';

        $add = new SemanticsExporter();

        $mapping = new FieldMappingCollection();
        $mapping->addMapping(
            new ScalarFieldMapping(
                $jira_story_points_field_id,
                $jira_story_points_field_label,
                null,
                $xml_story_points_id,
                'story_points',
                \Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
            )
        );

        $xml = new \SimpleXMLElement('<tracker><semantics></semantics></tracker>');

        $add->process(
            $xml,
            new PlatformConfiguration(),
            $mapping
        );

        $semantic = $xml->xpath('/tracker/semantics/semantic[@type="initial_effort"]');
        assertCount(0, $semantic);
    }

    public function testItDoesNotSetTheSemanticWhenFieldMappingDoesNotReferenceTheStoryPointField(): void
    {
        $jira_story_points_field_id = 'customfield_10004';

        $add = new SemanticsExporter();

        $platform_configuration = new PlatformConfiguration();
        $platform_configuration->setStoryPointsField($jira_story_points_field_id);

        $xml = new \SimpleXMLElement('<tracker><semantics></semantics></tracker>');

        $add->process(
            $xml,
            $platform_configuration,
            new FieldMappingCollection()
        );

        $semantic = $xml->xpath('/tracker/semantics/semantic[@type="initial_effort"]');
        assertCount(0, $semantic);
    }

    public function testItSetsTheSemanticOnTheMappedField(): void
    {
        $jira_story_points_field_id    = 'customfield_10004';
        $jira_story_points_field_label = 'Story points';
        $xml_story_points_id           = 'F123';

        $add = new SemanticsExporter();

        $platform_configuration = new PlatformConfiguration();
        $platform_configuration->setStoryPointsField($jira_story_points_field_id);

        $mapping = new FieldMappingCollection();
        $mapping->addMapping(
            new ScalarFieldMapping(
                $jira_story_points_field_id,
                $jira_story_points_field_label,
                null,
                $xml_story_points_id,
                'story_points',
                \Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
            )
        );

        $xml = new \SimpleXMLElement('<tracker><semantics></semantics></tracker>');

        $add->process(
            $xml,
            $platform_configuration,
            $mapping
        );

        $semantic = $xml->xpath('/tracker/semantics/semantic[@type="initial_effort"]')[0];
        assertNotNull($semantic->shortname);
        assertNotNull($semantic->label);
        assertNotNull($semantic->description);
        assertEquals($xml_story_points_id, $semantic->field['REF']);
    }
}
