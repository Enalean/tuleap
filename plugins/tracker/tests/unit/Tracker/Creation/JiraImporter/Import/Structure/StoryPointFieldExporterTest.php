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

namespace unit\Tracker\Creation\JiraImporter\Import\Structure;

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\StoryPointFieldExporter;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

final class StoryPointFieldExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var StoryPointFieldExporter
     */
    private $sp_exporter;
    /**
     * @var ContainersXMLCollection
     */
    private $xml_containers;
    /**
     * @var FieldMappingCollection
     */
    private $field_mapping;
    /**
     * @var \SimpleXMLElement
     */
    private $tracker_xml;
    /**
     * @var PlatformConfiguration
     */
    private $platform_configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $id_generator      = new FieldAndValueIDGenerator();
        $this->tracker_xml = new \SimpleXMLElement('<tracker><formElements/></tracker>');

        $this->xml_containers = new ContainersXMLCollection($id_generator);
        $this->xml_containers->addContainerInCollection(
            ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME,
            (new XMLFieldset($id_generator, ContainersXMLCollectionBuilder::RIGHT_COLUMN_NAME))
                ->export($this->tracker_xml->formElements)
        );

        $this->field_mapping = new FieldMappingCollection($id_generator);

        $this->platform_configuration = new PlatformConfiguration();

        $this->sp_exporter = new StoryPointFieldExporter(new FieldXmlExporter(new \XML_SimpleXMLCDATAFactory(), new FieldNameFormatter()), new NullLogger());
    }

    public function testItExportsStoryPointOnStoryTracker(): void
    {
        $this->platform_configuration->setStoryPointsField('customfield_10013');

        $this->sp_exporter->exportFields(
            $this->platform_configuration,
            $this->xml_containers,
            $this->field_mapping,
            new IssueType('10003', 'Story', false),
        );

        $story_points_xml = $this->tracker_xml->xpath('//formElement[name="right_column"]/formElements/formElement[name="story_points"]')[0];
        assertEquals(\Tracker_FormElementFactory::FIELD_FLOAT_TYPE, $story_points_xml['type']);

        $mapping = $this->field_mapping->getMappingFromJiraField('customfield_10013');
        assertEquals('story_points', $mapping->getFieldName());
        assertEquals($story_points_xml['ID'], $mapping->getXMLId());
    }

    public function testItDoesNotExportStoryPointFieldOnSubTaskTracker(): void
    {
        $this->platform_configuration->setStoryPointsField('customfield_10014');

        $this->sp_exporter->exportFields(
            $this->platform_configuration,
            $this->xml_containers,
            $this->field_mapping,
            new IssueType('10003', 'Sub-task', true),
        );

        $story_points_xml = $this->tracker_xml->xpath('//formElement[name="story_points"]');
        assertCount(0, $story_points_xml);
    }

    public function testItDoesNotExportStoryPointFieldWhenConfigurationHasNone(): void
    {
        $this->sp_exporter->exportFields(
            $this->platform_configuration,
            $this->xml_containers,
            $this->field_mapping,
            new IssueType('10003', 'Story', false),
        );

        $story_points_xml = $this->tracker_xml->xpath('//formElement[name="story_points"]');
        assertCount(0, $story_points_xml);
    }
}
