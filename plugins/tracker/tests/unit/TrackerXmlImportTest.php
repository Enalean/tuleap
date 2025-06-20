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

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_Artifact_XMLImport;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker\XML\Importer\CreateFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\GetInstanceFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\InstantiateTrackerFromXml;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\MappingsRegistry;
use User\XML\Import\IFindUserFromXMLReference;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerXmlImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerXmlImportFeedbackCollector
     */
    private $feedback_collector;

    /**
     * @var MappingsRegistry
     */
    private $mapping_registery;
    /**
     * @var \TrackerXmlImport
     */
    private $tracker_xml_importer;
    /**
     * @var \Project
     */
    private $project;

    /**
     * @var ImportConfig
     */
    private $configuration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var \EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerXMLFieldMappingFromExistingTracker
     */
    private $mapping_from_existing_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Workflow_Trigger_RulesManager
     */
    private $trigger_rules_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|XML_RNGValidator
     */
    private $rng_validator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HierarchyDAO
     */
    private $hierarchy_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_XMLImport
     */
    private $artifact_XML_import;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\Project\UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever_with_legacy;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExternalFieldsExtractor
     */
    private $external_validator;
    private TrackerCreationDataChecker $tracker_creation_data_checker;
    private GetInstanceFromXml&MockObject $get_instance_from_xml;
    private InstantiateTrackerFromXml&MockObject $instantiate_tracker_from_xml;
    private $create_from_xml;

    protected function setUp(): void
    {
        $this->tracker_factory = Mockery::spy(TrackerFactory::class);

        $this->external_validator           = Mockery::mock(ExternalFieldsExtractor::class);
        $this->ugroup_retriever_with_legacy = Mockery::spy(UGroupRetrieverWithLegacy::class);

        $this->tracker_form_element_factory  = Mockery::mock(Tracker_FormElementFactory::class);
        $this->artifact_XML_import           = Mockery::spy(Tracker_Artifact_XMLImport::class);
        $this->hierarchy_dao                 = Mockery::spy(HierarchyDAO::class);
        $this->rng_validator                 = new XML_RNGValidator();
        $this->trigger_rules_manager         = Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class);
        $this->mapping_from_existing_tracker = Mockery::spy(TrackerXMLFieldMappingFromExistingTracker::class);
        $this->event_manager                 = Mockery::spy(EventManager::class);
        $this->artifact_links_usage_dao      = Mockery::spy(ArtifactLinksUsageDao::class);
        $this->feedback_collector            = Mockery::mock(TrackerXmlImportFeedbackCollector::class);
        $this->tracker_creation_data_checker = Mockery::mock(TrackerCreationDataChecker::class);

        $this->get_instance_from_xml = $this->createMock(GetInstanceFromXml::class);

        $this->instantiate_tracker_from_xml = $this->createMock(InstantiateTrackerFromXml::class);
        $this->create_from_xml              = $this->createMock(CreateFromXml::class);

        $this->tracker_xml_importer = Mockery::mock(
            TrackerXmlImport::class,
            [
                $this->tracker_factory,
                $this->event_manager,
                $this->hierarchy_dao,
                $this->get_instance_from_xml,
                $this->rng_validator,
                $this->trigger_rules_manager,
                $this->artifact_XML_import,
                Mockery::spy(IFindUserFromXMLReference::class),
                Mockery::spy(LoggerInterface::class),
                Mockery::spy(ArtifactLinksUsageUpdater::class),
                $this->artifact_links_usage_dao,
                $this->mapping_from_existing_tracker,
                $this->external_validator,
                $this->feedback_collector,
                $this->create_from_xml,
                $this->instantiate_tracker_from_xml,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $this->external_validator->shouldReceive('extractExternalFieldFromProjectElement');

        $group_id      = 123;
        $this->project = Mockery::spy(Project::class);
        $this->project->shouldReceive('getId')->andReturns($group_id);

        $this->user = Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(1);

        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function testItShouldNotRaiseExceptionWithEmptyTrackerDescription(): void
    {
        $this->expectNotToPerformAssertions();
        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');

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

    public function testItReturnsEachSimpleXmlTrackerFromTheXmlInput(): void
    {
        $xml             = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));
        $trackers_result = $this->tracker_xml_importer->getAllXmlTrackersOrderedByPriority($xml);

        $xml_tracker1 = new SimpleXMLElement(
            '<tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                    <color>inca-silver</color>
                    <cannedResponses />
                  </tracker>'
        );

        $xml_tracker2 = new SimpleXMLElement(
            '<tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                    <color>inca-silver</color>
                    <cannedResponses />
                  </tracker>'
        );

        $xml_tracker3 = new SimpleXMLElement(
            '<tracker id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>name30</name>
                    <item_name>item31</item_name>
                    <description>desc32</description>
                    <color>inca-silver</color>
                    <cannedResponses />
                  </tracker>'
        );

        $expected_trackers = ['T101' => $xml_tracker1, 'T102' => $xml_tracker2, 'T103' => $xml_tracker3];

        $this->assertCount(3, $trackers_result);
        $this->assertEquals($expected_trackers, $trackers_result);
    }

    public function testItBuildsTrackersHierarchy(): void
    {
        $tracker            = new SimpleXMLElement(
            '<tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                  </tracker>'
        );
        $hierarchy          = [];
        $expected_hierarchy = [444 => [555]];
        $mapper             = ['T101' => 444, 'T102' => 555];
        $hierarchy          = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $tracker, $mapper);

        $this->assertNotEmpty($hierarchy);
        $this->assertNotNull($hierarchy[444]);
        $this->assertEquals($hierarchy, $expected_hierarchy);
    }

    public function testItAddsTrackersHierarchyOnExistingHierarchy(): void
    {
        $hierarchy          = [444 => [555]];
        $expected_hierarchy = [444 => [555, 666]];
        $mapper             = ['T101' => 444, 'T103' => 666];
        $xml_tracker        = new SimpleXMLElement(
            '<tracker id="T103" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);

        $this->assertNotEmpty($hierarchy);
        $this->assertNotNull($hierarchy[444]);
        $this->assertEquals($expected_hierarchy, $hierarchy);
    }

    public function testItCollectsErrorsWithoutImporting(): void
    {
        $this->get_instance_from_xml->expects($this->exactly(3))->method('getInstanceFromXML');
        $this->tracker_xml_importer->shouldReceive('import')->never();
        $this->tracker_factory->shouldReceive('collectTrackersNameInErrorOnMandatoryCreationInfo')->once();

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

        $this->hierarchy_dao->shouldReceive('updateChildren')->with(2);

        $this->external_validator->shouldReceive('extractExternalFieldsFromTracker');

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

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::type(ImportXMLProjectTrackerDone::class))->once();
        $this->external_validator->shouldReceive('extractExternalFieldsFromTracker');

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
        $this->artifact_links_usage_dao->shouldReceive('isTypeDisabledInProject')
            ->with(Mockery::any(), '_is_child')
            ->andReturn(true);

        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $this->instantiate_tracker_from_xml->method('instantiateTrackerFromXml');

        $this->hierarchy_dao->shouldReceive('updateChildren')->never();
        $this->external_validator->shouldReceive('extractExternalFieldsFromTracker');

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

        $this->artifact_XML_import->shouldReceive('importBareArtifactsFromXML')->once()->andReturn([]);

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

        $tracker = TrackerTestBuilder::aTracker()->withId(10)->build();

        $this->tracker_xml_importer->shouldReceive('updateFromXML')->andReturns($tracker);
        $this->artifact_XML_import->shouldReceive('importBareArtifactsFromXML')->andReturn([]);
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
