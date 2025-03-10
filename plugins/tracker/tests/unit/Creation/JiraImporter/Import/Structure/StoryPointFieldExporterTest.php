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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotContains;
use function PHPUnit\Framework\assertNull;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StoryPointFieldExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StoryPointFieldExporter $sp_exporter;
    private PlatformConfiguration $platform_configuration;
    private XMLTracker $xml_tracker;
    private IDGenerator $id_generator;
    private FieldMappingCollection $field_mapping_collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id_generator = new FieldAndValueIDGenerator();

        $this->platform_configuration = new PlatformConfiguration();

        $jira_client = new class extends JiraCloudClientStub {
        };

        $this->field_mapping_collection = new FieldMappingCollection();

        $builder           = new AlwaysThereFieldsExporter();
        $this->xml_tracker = $builder->exportFields(
            new XMLTracker($this->id_generator, 'whatever'),
            new StatusValuesCollection(
                $jira_client,
                new NullLogger()
            ),
            $this->field_mapping_collection,
        );


        $this->sp_exporter = new StoryPointFieldExporter(new NullLogger());
    }

    public function testItExportsStoryPointOnStoryTracker(): void
    {
        $this->platform_configuration->setStoryPointsField('customfield_10013');

        $xml_tracker = $this->sp_exporter->exportFields(
            $this->platform_configuration,
            $this->xml_tracker,
            $this->field_mapping_collection,
            new IssueType('10003', 'Story', false),
            $this->id_generator,
        );

        $xml              = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');
        $exported_tracker = $xml_tracker->export($xml);

        $story_points_xml = $exported_tracker->xpath('//formElement[name="right_column"]/formElements/formElement[name="story_points"]')[0];
        assertEquals(\Tracker_FormElementFactory::FIELD_FLOAT_TYPE, $story_points_xml['type']);

        $mapping = $this->field_mapping_collection->getMappingFromJiraField('customfield_10013');
        assertEquals('story_points', $mapping->getFieldName());
    }

    public function testItDoesNotExportStoryPointFieldOnSubTaskTracker(): void
    {
        $this->platform_configuration->setStoryPointsField('customfield_10014');

        $xml_tracker = $this->sp_exporter->exportFields(
            $this->platform_configuration,
            $this->xml_tracker,
            $this->field_mapping_collection,
            new IssueType('10003', 'Sub-task', true),
            $this->id_generator,
        );

        $xml              = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');
        $exported_tracker = $xml_tracker->export($xml);

        $story_points_xml = $exported_tracker->xpath('//formElement[name="story_points"]');
        assertCount(0, $story_points_xml);

        assertNull($this->field_mapping_collection->getMappingFromJiraField('customfield_10014'));
    }

    public function testItDoesNotExportStoryPointFieldWhenConfigurationHasNone(): void
    {
        $xml_tracker = $this->sp_exporter->exportFields(
            $this->platform_configuration,
            $this->xml_tracker,
            $this->field_mapping_collection,
            new IssueType('10003', 'Story', false),
            $this->id_generator,
        );


        $xml              = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers/></project>');
        $exported_tracker = $xml_tracker->export($xml);

        $story_points_xml = $exported_tracker->xpath('//formElement[name="story_points"]');
        assertCount(0, $story_points_xml);

        assertNotContains('story_points', array_map(static fn (FieldMapping $mapping) => $mapping->getFieldName(), $this->field_mapping_collection->getAllMappings()));
    }
}
