<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Event;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_Artifact_XMLImport;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker\XML\Importer\CreateFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\GetInstanceFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\InstantiateTrackerFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\TrackersHierarchyBuilder;
use Tuleap\Tracker\Tracker\XML\Importer\XmlTrackersByPriorityOrderer;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\MappingsRegistry;
use User\XML\Import\IFindUserFromXMLReference;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerXmlImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;

    private MappingsRegistry $mapping_registery;
    private TrackerXmlImport $tracker_xml_importer;

    private Project $project;

    private ImportConfig $configuration;

    private TrackerFactory&MockObject $tracker_factory;

    private EventManager&MockObject $event_manager;

    private ArtifactLinksUsageDao&MockObject $artifact_links_usage_dao;
    private XML_RNGValidator $rng_validator;
    private HierarchyDAO&MockObject $hierarchy_dao;
    private Tracker_Artifact_XMLImport&MockObject $artifact_XML_import;
    private Tracker_FormElementFactory $tracker_form_element_factory;
    private ExternalFieldsExtractor&MockObject $external_validator;
    private GetInstanceFromXml&MockObject $get_instance_from_xml;
    private InstantiateTrackerFromXml&MockObject $instantiate_tracker_from_xml;

    protected function setUp(): void
    {
        $this->tracker_factory = $this->createMock(TrackerFactory::class);

        $this->external_validator = $this->createMock(ExternalFieldsExtractor::class);

        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->artifact_XML_import          = $this->createMock(Tracker_Artifact_XMLImport::class);
        $this->hierarchy_dao                = $this->createMock(HierarchyDAO::class);
        $this->rng_validator                = new XML_RNGValidator();
        $trigger_rules_manager              = $this->createMock(Tracker_Workflow_Trigger_RulesManager::class);
        $mapping_from_existing_tracker      = new TrackerXMLFieldMappingFromExistingTracker();
        $this->event_manager                = $this->createMock(EventManager::class);
        $this->artifact_links_usage_dao     = $this->createMock(ArtifactLinksUsageDao::class);
        $feedback_collector                 = $this->createMock(TrackerXmlImportFeedbackCollector::class);

        $this->get_instance_from_xml = $this->createMock(GetInstanceFromXml::class);

        $this->instantiate_tracker_from_xml = $this->createMock(InstantiateTrackerFromXml::class);
        $create_from_xml                    = $this->createMock(CreateFromXml::class);

        $artifact_links_usage_updater = $this->createMock(ArtifactLinksUsageUpdater::class);
        $artifact_links_usage_updater->method('forceUsageOfArtifactLinkTypes');

        $this->tracker_xml_importer = new TrackerXmlImport(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            $this->get_instance_from_xml,
            $this->rng_validator,
            $trigger_rules_manager,
            $this->artifact_XML_import,
            $this->createMock(IFindUserFromXMLReference::class),
            new NullLogger(),
            $artifact_links_usage_updater,
            $this->artifact_links_usage_dao,
            $mapping_from_existing_tracker,
            $this->external_validator,
            $feedback_collector,
            $create_from_xml,
            $this->instantiate_tracker_from_xml,
            new XmlTrackersByPriorityOrderer(),
            new TrackersHierarchyBuilder(),
        );

        $this->external_validator->method('extractExternalFieldFromProjectElement');

        $this->project = ProjectTestBuilder::aProject()->withId(123)->build();
        $this->user    = UserTestBuilder::aUser()->withId(1)->build();

        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function testItShouldNotRaiseExceptionWithEmptyTrackerDescription(): void
    {
        $this->expectNotToPerformAssertions();
        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');

        $this->event_manager->method('processEvent');
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject');

        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '', $this->user);
    }

    public function testItShouldNotRaiseExceptionWithOnlyWhitespacesTrackerDescription(): void
    {
        $this->expectNotToPerformAssertions();
        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');

        $this->event_manager->method('processEvent');
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject');

        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[              ]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '', $this->user);
    }

    public function testItShouldRaiseExceptionWithEmptyTrackerName(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '', $this->user);
    }

    public function testItShouldRaiseExceptionWithOnlyWhitespacesTrackerName(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[              ]]></name>
                        <item_name>shortname</item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '', $this->user);
    }

    public function testItShouldRaiseExceptionWithEmptyTrackerShortName(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '', $this->user);
    }

    public function testItShouldRaiseExceptionWithInvalidTrackerShortName(): void
    {
        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name>-------------</item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                    </tracker>
                </trackers>
            </project>'
        );

        $this->expectException('XML_ParseException');
        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '', $this->user);
    }

    public function testItAllowsItemsLabelToHavePlusCharacter(): void
    {
        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');

        $this->event_manager->method('processEvent');
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject');

        $xml_input = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name><![CDATA[Name]]></name>
                        <item_name><![CDATA[ShortName]]></item_name>
                        <description><![CDATA[Description]]></description>
                        <cannedResponses/>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="0">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                        <item ID="V2065" label="Code review+" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2064"/>
                                    </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                    </tracker>
                </trackers>
            </project>'
        );

        $expected_tracker_mapping = ['T101' => null];

        $created_trackers_mapping = $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml_input,
            $this->mapping_registery,
            '',
            $this->user
        );

        $this->assertEquals($expected_tracker_mapping, $created_trackers_mapping);
    }

    public function testItCollectsErrorsWithoutImporting(): void
    {
        $this->get_instance_from_xml->expects($this->exactly(3))->method('getInstanceFromXML');
        $this->tracker_factory->expects($this->once())->method('collectTrackersNameInErrorOnMandatoryCreationInfo');

        $xml_input = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $collected_errors = $this->tracker_xml_importer->collectErrorsWithoutImporting($this->project, $xml_input);
        $this->assertEquals('', $collected_errors);
    }

    public function testItCreatesAllTrackersAndStoresTrackersHierarchy(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $tracker_101 = TrackerTestBuilder::aTracker()->withId(444)->build();
        $tracker_102 = TrackerTestBuilder::aTracker()->withId(555)->build();
        $tracker_103 = TrackerTestBuilder::aTracker()->withId(666)->build();

        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml')
            ->willReturnCallback(
                static fn (
                    Project $project,
                    SimpleXMLElement $xml_tracker,
                ) => match (true) {
                    (string) $xml_tracker->name === 'name10' => $tracker_101,
                    (string) $xml_tracker->name === 'name20' => $tracker_102,
                    (string) $xml_tracker->name === 'name30' => $tracker_103,
                }
            );

        $this->hierarchy_dao->method('updateChildren')->willReturnCallback(
            static fn (int $parent_id, array $child_ids) => match ($parent_id) {
                444, 555 => true,
            }
        );

        $this->external_validator->method('extractExternalFieldsFromTracker');

        $this->event_manager->method('processEvent');
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject');

        $result = $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );

        $expected_mapping = [
            'T101' => 444,
            'T102' => 555,
            'T103' => 666,
        ];

        $this->assertEquals($expected_mapping, $result);
    }

    public function testItThrowsAnEventIfAllTrackersAreCreated(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject');
        $this->hierarchy_dao->method('updateChildren');

        $this->event_manager
            ->expects($this->exactly(2))
            ->method('processEvent')
            ->willReturnCallback(
                static fn (object|string $event) => match (true) {
                    $event instanceof ImportXMLProjectTrackerDone,
                    $event === Event::IMPORT_COMPAT_REF_XML => true
                }
            );
        $this->external_validator->method('extractExternalFieldsFromTracker');

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );
    }

    public function testItShouldNotImportHierarchyIfIsChildIsNotUsed(): void
    {
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject')
            ->with($this->anything(), '_is_child')
            ->willReturn(true);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');

        $this->hierarchy_dao->expects($this->never())->method('updateChildren');
        $this->external_validator->method('extractExternalFieldsFromTracker');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );
    }

    public function testItImportsArtifacts(): void
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name>name10</name>
                        <item_name>item11</item_name>
                        <description>desc12</description>
                        <cannedResponses/>
                        <artifacts/>
                    </tracker>
                </trackers>
             </project>'
        );

        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml')->willReturn($tracker);

        $this->artifact_XML_import->expects($this->once())->method('importBareArtifactsFromXML')->willReturn([]);
        $this->artifact_XML_import->method('importArtifactChangesFromXML');

        $this->event_manager->method('processEvent');
        $this->artifact_links_usage_dao->method('isTypeDisabledInProject');

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );
    }

    public function testItImportsUpdatedArtifacts(): void
    {
        $this->expectNotToPerformAssertions();

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                     <trackers>
                         <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                             <name>name10</name>
                             <item_name>item11</item_name>
                             <description>desc12</description>
                         </tracker>
                     </trackers>'
        );

        $this->artifact_XML_import->method('importBareArtifactsFromXML')->willReturn([]);
        $configuration = new ImportConfig();
        $this->tracker_xml_importer->import(
            $configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );
    }
}
