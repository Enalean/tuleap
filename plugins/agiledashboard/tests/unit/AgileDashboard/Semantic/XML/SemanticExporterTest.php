<?php
/*
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

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

final class SemanticExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCatchWhenProvidedXMLIsInvalid(): void
    {
        $add = new SemanticsExporter();

        $this->expectException(\LogicException::class);

        $add->process(
            new \SimpleXMLElement('<tracker />'),
            new PlatformConfiguration(),
            new FieldMappingCollection(new FieldAndValueIDGenerator()),
            new StatusValuesCollection($this->getJiraClient(), new NullLogger()),
        );
    }

    public function testItDoesNotSetTheSemanticWhenPlatformDoesnotHaveStoryPointField(): void
    {
        $jira_story_points_field_id = 'customfield_10004';
        $xml_story_points_id        = 'F123';

        $add = new SemanticsExporter();

        $mapping = new FieldMappingCollection(new FieldAndValueIDGenerator());
        $mapping->addMapping(
            new ScalarFieldMapping(
                $jira_story_points_field_id,
                $xml_story_points_id,
                'story_points',
                \Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
            )
        );

        $xml = new \SimpleXMLElement('<tracker><semantics></semantics></tracker>');

        $add->process(
            $xml,
            new PlatformConfiguration(),
            $mapping,
            new StatusValuesCollection($this->getJiraClient(), new NullLogger())
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
            new FieldMappingCollection(new FieldAndValueIDGenerator()),
            new StatusValuesCollection($this->getJiraClient(), new NullLogger())
        );

        $semantic = $xml->xpath('/tracker/semantics/semantic[@type="initial_effort"]');
        assertCount(0, $semantic);
    }

    public function testItSetsTheSemanticOnTheMappedField(): void
    {
        $jira_story_points_field_id = 'customfield_10004';
        $xml_story_points_id        = 'F123';

        $add = new SemanticsExporter();

        $platform_configuration = new PlatformConfiguration();
        $platform_configuration->setStoryPointsField($jira_story_points_field_id);

        $mapping = new FieldMappingCollection(new FieldAndValueIDGenerator());
        $mapping->addMapping(
            new ScalarFieldMapping(
                $jira_story_points_field_id,
                $xml_story_points_id,
                'story_points',
                \Tracker_FormElementFactory::FIELD_FLOAT_TYPE,
            )
        );

        $xml = new \SimpleXMLElement('<tracker><semantics></semantics></tracker>');

        $add->process(
            $xml,
            $platform_configuration,
            $mapping,
            new StatusValuesCollection($this->getJiraClient(), new NullLogger())
        );

        $semantic = $xml->xpath('/tracker/semantics/semantic[@type="initial_effort"]')[0];
        assertNotNull($semantic->shortname);
        assertNotNull($semantic->label);
        assertNotNull($semantic->description);
        assertEquals($xml_story_points_id, $semantic->field['REF']);
    }

    public function testItAddsTheDoneSemantic(): void
    {
        $id_generator             = new FieldAndValueIDGenerator();
        $status_values_collection = new StatusValuesCollection($this->getJiraClient(), new NullLogger());
        $status_values_collection->initCollectionWithValues(
            [],
            [
                JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponse(['id' => '10005', 'name' => 'done'], $id_generator),
            ],
        );

        $xml = new \SimpleXMLElement('<tracker><semantics></semantics></tracker>');

        $add = new SemanticsExporter();
        $add->process(
            $xml,
            new PlatformConfiguration(),
            new FieldMappingCollection(new FieldAndValueIDGenerator()),
            $status_values_collection,
        );

        $semantic = $xml->xpath('/tracker/semantics/semantic[@type="done"]');
        assertCount(1, $semantic);
        assertCount(1, $semantic[0]->closed_values->closed_value);
        assertEquals('V1', $semantic[0]->closed_values->closed_value[0]['REF']);
    }

    private function getJiraClient(): JiraClient
    {
        return new class implements JiraClient
        {
            public function getUrl(string $url): ?array
            {
                return [];
            }
        };
    }
}
