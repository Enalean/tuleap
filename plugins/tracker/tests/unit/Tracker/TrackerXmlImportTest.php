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
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_XMLImport;
use Tracker_CannedResponseFactory;
use Tracker_FormElement_Field_Date;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use Tracker_RuleFactory;
use Tracker_SemanticFactory;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use TrackerFromXmlImportCannotBeCreatedException;
use TrackerXmlImport;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationNotificationsSettingsFromXmlBuilder;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\Tracker\XML\Importer\TrackerExtraConfiguration;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\MappingsRegistry;
use User\XML\Import\IFindUserFromXMLReference;
use WorkflowFactory;
use XML_RNGValidator;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $field1685;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectUGroup
     */
    private $contributors_ugroup;
    /**
     * @var int
     */
    private $contributors_ugroup_id;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\Project\UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever_with_legacy;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExternalFieldsExtractor
     */
    private $external_validator;
    private TrackerCreationDataChecker $tracker_creation_data_checker;

    protected function setUp(): void
    {
        $this->tracker_factory = Mockery::spy(TrackerFactory::class);

        $this->field1685 = Mockery::spy(Tracker_FormElement_Field_Date::class);

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
        $this->tracker_xml_importer          = Mockery::mock(
            TrackerXmlImport::class,
            [
                $this->tracker_factory,
                $this->event_manager,
                $this->hierarchy_dao,
                Mockery::spy(Tracker_CannedResponseFactory::class),
                $this->tracker_form_element_factory,
                Mockery::spy(Tracker_SemanticFactory::class),
                Mockery::spy(Tracker_RuleFactory::class),
                Mockery::spy(Tracker_ReportFactory::class),
                Mockery::spy(WorkflowFactory::class),
                $this->rng_validator,
                $this->trigger_rules_manager,
                $this->artifact_XML_import,
                Mockery::spy(IFindUserFromXMLReference::class),
                $this->ugroup_retriever_with_legacy,
                Mockery::spy(LoggerInterface::class),
                Mockery::spy(ArtifactLinksUsageUpdater::class),
                $this->artifact_links_usage_dao,
                Mockery::spy(WebhookFactory::class),
                $this->mapping_from_existing_tracker,
                $this->external_validator,
                $this->feedback_collector,
                $this->tracker_creation_data_checker,
                new TrackerCreationNotificationsSettingsFromXmlBuilder(),
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
        $this->tracker_xml_importer->shouldReceive('createFromXML')->andReturn(\Mockery::spy(\Tracker::class));

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
        $this->tracker_xml_importer->shouldReceive('createFromXML')->andReturn(\Mockery::spy(\Tracker::class));

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
        $this->tracker_xml_importer->shouldReceive('createFromXML')->andReturn(\Mockery::spy(\Tracker::class));

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

    public function testGetInstanceFromXmlTryToExtractEverything(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/EmptyTracker.xml'));

        $tracker = Mockery::mock(Tracker::class);
        $this->tracker_xml_importer->shouldReceive('setTrackerGeneralInformation')->once();
        $this->tracker_factory->shouldReceive('getInstanceFromRow')->once()->andReturn($tracker);
        $this->tracker_xml_importer->shouldReceive('setCannedResponses')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setFormElementFields')->once()->withArgs(
            [$xml, $tracker]
        );
        $this->tracker_xml_importer->shouldReceive('setSemantics')->once()->withArgs([$xml, $tracker, null]);
        $this->tracker_xml_importer->shouldReceive('setLegacyDependencies')->once()->withArgs([$xml]);
        $this->tracker_xml_importer->shouldReceive('setRules')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setTrackerReports')->once()->withArgs(
            [$xml, $this->project, $tracker]
        );
        $this->tracker_xml_importer->shouldReceive('setWorkflow')->once()->withArgs([$xml, $this->project, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setWebhooks')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setPermissions')->once()->withArgs(
            [$xml, $this->project, $tracker, []]
        );

        $tracker->shouldReceive('getFormElementFields')->andReturn([]);

        $this->tracker_xml_importer->getInstanceFromXML(
            $xml,
            $this->project,
            "tracker name",
            "trcker description",
            "bugs",
            "peggy-pink",
            []
        );
    }

    public function testGetInstanceFromXmlExtractPermissionsFromXml(): void
    {
        $xml_input = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/PermissionTracker.xml'));

        $this->contributors_ugroup    = Mockery::spy(ProjectUGroup::class);
        $this->contributors_ugroup_id = 42;
        $this->contributors_ugroup->shouldReceive('getId')->andReturns($this->contributors_ugroup_id);

        $this->ugroup_retriever_with_legacy->shouldReceive('getUGroupId')
            ->withArgs([$this->project, 'Contributors'])
            ->andReturn($this->contributors_ugroup_id)
            ->once();
        $this->ugroup_retriever_with_legacy->shouldReceive('getUGroupId')
            ->withArgs([$this->project, 'UGROUP_REGISTERED'])
            ->andReturn(3)
            ->once();
        $this->ugroup_retriever_with_legacy->shouldReceive('getUGroupId')
            ->withArgs([$this->project, 'UGROUP_PROJECT_MEMBERS'])
            ->andReturn(4)
            ->once();

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getName')->andReturn("bugs");
        $tracker->shouldReceive('setCachePermission')
            ->with($this->contributors_ugroup_id, 'PLUGIN_TRACKER_ACCESS_FULL')->once();
        $tracker->shouldReceive('setCachePermission')->with(3, 'PLUGIN_TRACKER_ACCESS_FULL')->once();
        $this->field1685->shouldReceive('setCachePermission')->with(4, 'PLUGIN_TRACKER_FIELD_UPDATE')->once();

        $field_mapping = [
            'F1685' => $this->field1685,
        ];

        $this->tracker_xml_importer->setPermissions(
            $xml_input,
            $this->project,
            $tracker,
            $field_mapping
        );
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

        $expected_trackers = ["T101" => $xml_tracker1, "T102" => $xml_tracker2, "T103" => $xml_tracker3];

        $this->assertCount(3, $trackers_result);
        $this->assertEquals($expected_trackers, $trackers_result);
    }

    public function testItExtractGeneralSettingsFromImport(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TestTracker-1.xml'));
        $row = $this->tracker_xml_importer->setTrackerGeneralInformation(
            $xml,
            $this->project,
            'Tracker public name',
            '',
            'bugs',
            null
        );

        $expected_row = [
            'id'                           => 0,
            'name'                         => 'Tracker public name',
            'group_id'                     => 123,
            'description'                  => '',
            'item_name'                    => 'bugs',
            'submit_instructions'          => 'some submit instructions',
            'browse_instructions'          => 'and some for browsing',
            'status'                       => '',
            'deletion_date'                => '',
            'color'                        => 'inca-silver',
            'allow_copy'                   => 0,
            'enable_emailgateway'          => 0,
            'instantiate_for_new_projects' => 1,
            'log_priority_changes'         => 0,
            'notifications_level'          => Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
        ];

        $this->assertEquals($expected_row, $row);
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
        $mapper             = ["T101" => 444, "T102" => 555];
        $hierarchy          = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $tracker, $mapper);

        $this->assertNotEmpty($hierarchy);
        $this->assertNotNull($hierarchy[444]);
        $this->assertEquals($hierarchy, $expected_hierarchy);
    }

    public function testItAddsTrackersHierarchyOnExistingHierarchy(): void
    {
        $hierarchy          = [444 => [555]];
        $expected_hierarchy = [444 => [555, 666]];
        $mapper             = ["T101" => 444, "T103" => 666];
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
        $this->tracker_xml_importer->shouldReceive('getInstanceFromXML')->times(3);
        $this->tracker_xml_importer->shouldReceive('import')->never();
        $this->tracker_factory->shouldReceive('collectTrackersNameInErrorOnMandatoryCreationInfo')->once();

        $xml_input = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $collected_errors = $this->tracker_xml_importer->collectErrorsWithoutImporting($this->project, $xml_input);
        $this->assertEquals('', $collected_errors);
    }

    public function testItCreatesAllTrackersAndStoresTrackersHierarchy(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $this->mockCreateAlwaysThereTrackers();
        $this->mockTracker103();

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
            "T101" => 444,
            "T102" => 555,
            "T103" => 666,
        ];

        $this->assertEquals($expected_mapping, $result);
    }

    public function testItReUsesTrackerAtTrackerImport(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $extra_configuration = new TrackerExtraConfiguration(['item31']);
        $this->configuration->addExtraConfiguration($extra_configuration);

        $tracker_103 = Mockery::mock(Tracker::class);
        $tracker_103->shouldReceive('getId')->andReturn(666);

        $this->mockCreateAlwaysThereTrackers();

        $this->tracker_factory->shouldReceive('getTrackerByShortnameAndProjectId')
            ->with('item31', 123)->once()->andReturns($tracker_103);
        $this->tracker_form_element_factory->shouldReceive('getFields')->with($tracker_103)->once()->andReturns([]);
        $this->mapping_from_existing_tracker->shouldReceive('getXmlFieldsMapping')->once()->andReturns([]);

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
            "T101" => 444,
            "T102" => 555,
            "T103" => 666,
        ];

        $this->assertEquals($expected_mapping, $result);
    }

    public function testItThrowsAnEventIfAllTrackersAreCreated(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $this->mockCreateAlwaysThereTrackers();
        $this->mockTracker103();

        $expected_mapping = [
            "T101" => 444,
            "T102" => 555,
            "T103" => 666,
        ];

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::type(ImportXMLProjectTrackerDone::class));
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


        $this->mockCreateAlwaysThereTrackers();
        $this->mockTracker103();

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
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TrackersList.xml'));

        $this->mockCreateAlwaysThereTrackers();
        $this->mockTracker103();

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );
    }

    public function testItImportFormElementAndExternalField()
    {
        $xml                = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                     <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                         <name>name10</name>
                         <item_name>item11</item_name>
                         <description>desc12</description>
                         <color>inca-silver</color>
                         <cannedResponses/>
                         <formElements>
                            <formElement type="string" ID="F691" rank="0" required="1">
                              <name>summary</name>
                              <label><![CDATA[Summary]]></label>
                              <description><![CDATA[One line description of the artifact]]></description>
                              <properties maxchars="150" size="80"/>
                             </formElement>
                             <externalField/>
                             <paformElement/>
                         </formElements>
                     </tracker>'
        );
        $feedback_collector = Mockery::mock(TrackerXmlImportFeedbackCollector::class);
        $tracker            = Mockery::mock(Tracker::class);
        $this->tracker_xml_importer->shouldReceive('setTrackerGeneralInformation')->once();
        $this->tracker_factory->shouldReceive('getInstanceFromRow')->once()->andReturn($tracker);
        $this->tracker_xml_importer->shouldReceive('setCannedResponses')->once()->withArgs([$xml, $tracker]);
        $this->tracker_form_element_factory->shouldReceive('getInstanceFromXML')->twice();

        $this->tracker_xml_importer->shouldReceive('setSemantics')->once()->withArgs([$xml, $tracker, []]);
        $this->tracker_xml_importer->shouldReceive('setLegacyDependencies')->once()->withArgs([$xml]);
        $this->tracker_xml_importer->shouldReceive('setRules')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setTrackerReports')->once()->withArgs(
            [$xml, $this->project, $tracker]
        );
        $this->tracker_xml_importer->shouldReceive('setWorkflow')->once()->withArgs([$xml, $this->project, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setWebhooks')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setPermissions')->once()->withArgs(
            [$xml, $this->project, $tracker, []]
        );

        $tracker->shouldReceive('getFormElementFields')->andReturn([]);

        $this->tracker_xml_importer->getInstanceFromXML(
            $xml,
            $this->project,
            "tracker name",
            "trcker description",
            "bugs",
            'peggy-pink',
            [],
            $feedback_collector,
            $this->user
        );
    }

    public function testWarnUserIfAFieldHasNoPermission()
    {
        $xml                = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                     <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                         <name>name10</name>
                         <item_name>item11</item_name>
                         <description>desc12</description>
                         <color>inca-silver</color>
                         <cannedResponses/>
                         <formElements>
                            <formElement type="string" ID="F691" rank="0" required="1"/>
                            <formElement type="string" ID="F692" rank="0" required="1"/>
                         </formElements>
                     </tracker>'
        );
        $feedback_collector = Mockery::mock(TrackerXmlImportFeedbackCollector::class);
        $tracker            = Mockery::mock(Tracker::class);

        $this->tracker_xml_importer->shouldReceive('setTrackerGeneralInformation')->once();
        $this->tracker_factory->shouldReceive('getInstanceFromRow')->once()->andReturn($tracker);
        $this->tracker_xml_importer->shouldReceive('setCannedResponses')->once()->withArgs([$xml, $tracker]);
        $this->tracker_form_element_factory->shouldReceive('getInstanceFromXML')->twice();

        $this->tracker_xml_importer->shouldReceive('setSemantics')->once()->withArgs([$xml, $tracker, null]);
        $this->tracker_xml_importer->shouldReceive('setLegacyDependencies')->once()->withArgs([$xml]);
        $this->tracker_xml_importer->shouldReceive('setRules')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setTrackerReports')->once()->withArgs(
            [$xml, $this->project, $tracker]
        );
        $this->tracker_xml_importer->shouldReceive('setWorkflow')->once()->withArgs([$xml, $this->project, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setWebhooks')->once()->withArgs([$xml, $tracker]);
        $this->tracker_xml_importer->shouldReceive('setPermissions')->once();

        $this->initXmlFieldMapping($xml, $tracker);

        $this->feedback_collector->shouldReceive("addWarnings")
            ->with("Tracker tracker_name : field field_2 (F692) has no permission")->once();

        $this->tracker_xml_importer->getInstanceFromXML(
            $xml,
            $this->project,
            "tracker name",
            "trcker description",
            "bugs",
            'peggy-pink',
            [],
            $feedback_collector,
            $this->user
        );
    }

    private function initXmlFieldMapping(SimpleXMLElement $xml_tracker, Tracker $tracker): void
    {
        $tracker->shouldReceive("getName")->andReturn("tracker_name");

        $this->tracker_factory->shouldReceive('getTrackerByShortnameAndProjectId')->andReturn($tracker);
        $field_1 = Mockery::mock(\Tracker_FormElement_Field::class);
        $field_1->shouldReceive("getName")->andReturn("field_1");
        $field_1->shouldReceive("hasCachedPermissions")->andReturn(true);

        $field_2 = Mockery::mock(\Tracker_FormElement_Field::class);
        $field_2->shouldReceive("getName")->andReturn("field_2");
        $field_2->shouldReceive("hasCachedPermissions")->andReturn(false);

        $this->tracker_form_element_factory->shouldReceive("getFields")->andReturn([]);
        $this->mapping_from_existing_tracker->shouldReceive("getXmlFieldsMapping")->andReturn(["F691" => $field_1, "F692" => $field_2]);

        $tracker->shouldReceive('getFormElementFields')->andReturn([
            $field_1,
            $field_2,
        ]);

        $this->tracker_xml_importer->updateFromXML($this->project, $xml_tracker);
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

        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(10);

        $this->tracker_xml_importer->shouldReceive('updateFromXML')->andReturns($tracker);
        $this->artifact_XML_import->shouldReceive('importBareArtifactsFromXML')->andReturn([]);
        $configuration = new ImportConfig();
        $configuration->setUpdate(true);
        $this->tracker_xml_importer->import(
            $configuration,
            $this->project,
            $xml,
            $this->mapping_registery,
            '',
            $this->user
        );
    }

    public function testInstantiateTrackerFromXmlReturnsAlreadyExistingTracker(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID');
        $xml                 = new SimpleXMLElement('<tracker><item_name>existing_tracker</item_name></tracker>');
        $import_config       = Mockery::mock(ImportConfig::class);
        $extra_configuration = Mockery::mock(TrackerExtraConfiguration::class);
        $extra_configuration->shouldReceive('getServiceName')->andReturn(\trackerPlugin::SERVICE_SHORTNAME);
        $extra_configuration->shouldReceive('getValue')->andReturn(["existing_tracker"]);

        $this->tracker_form_element_factory->shouldReceive('getFields')->andReturn([]);
        $this->mapping_from_existing_tracker->shouldReceive('getXmlFieldsMapping');

        $tracker = Mockery::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerByShortnameAndProjectId')->andReturn($tracker);

        $import_config->shouldReceive('getExtraConfiguration')->once()->andReturn([$extra_configuration]);

        $instantiated_tracker = $this->tracker_xml_importer->instantiateTrackerFromXml($project, $xml, $import_config, []);

        $this->assertEquals($tracker, $instantiated_tracker);
    }

    public function testInstantiateTrackerFromXmlUpdateTracker(): void
    {
        $project       = Mockery::mock(Project::class);
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project/>');
        $import_config = Mockery::mock(ImportConfig::class);
        $import_config->shouldReceive('isUpdate')->andReturnTrue();
        $import_config->shouldReceive('getExtraConfiguration')->once()->andReturn([]);
        $tracker = Mockery::mock(Tracker::class);

        $this->tracker_xml_importer->shouldReceive('updateFromXML')->once()->andReturn($tracker);

        $instantiated_tracker = $this->tracker_xml_importer->instantiateTrackerFromXml($project, $xml, $import_config, []);

        $this->assertEquals($tracker, $instantiated_tracker);
    }

    public function testInstantiateTrackerFromXmlCreateTracker(): void
    {
        $project       = Mockery::mock(Project::class);
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project/>');
        $import_config = Mockery::mock(ImportConfig::class);
        $import_config->shouldReceive('isUpdate')->andReturnFalse();
        $import_config->shouldReceive('getExtraConfiguration')->once()->andReturn([]);
        $tracker = Mockery::mock(Tracker::class);

        $this->tracker_xml_importer->shouldReceive('createFromXML')->once()->andReturn($tracker);

        $instantiated_tracker = $this->tracker_xml_importer->instantiateTrackerFromXml($project, $xml, $import_config, []);

        $this->assertEquals($tracker, $instantiated_tracker);
    }

    public function testInstantiateTrackerFromXmlDisplayErrorsBeforeThrowingAGlobalException(): void
    {
        $project       = Mockery::mock(Project::class);
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project/>');
        $import_config = Mockery::mock(ImportConfig::class);
        $import_config->shouldReceive('isUpdate')->andReturnFalse();
        $import_config->shouldReceive('getExtraConfiguration')->once()->andReturn([]);

        $this->tracker_xml_importer->shouldReceive('createFromXML')->andThrow(
            TrackerIsInvalidException::invalidTrackerTemplate()
        );

        $this->feedback_collector->shouldReceive('addErrors')->once();
        $this->feedback_collector->shouldReceive('displayErrors')->once();

        $this->expectException(TrackerFromXmlImportCannotBeCreatedException::class);

        $this->tracker_xml_importer->instantiateTrackerFromXml($project, $xml, $import_config, []);
    }

    private function mockCreateAlwaysThereTrackers(): void
    {
        $tracker_101 = Mockery::mock(Tracker::class);
        $tracker_101->shouldReceive('getId')->andReturn(444);

        $tracker_102 = Mockery::mock(Tracker::class);
        $tracker_102->shouldReceive('getId')->andReturn(555);

        $this->tracker_xml_importer->shouldReceive('createFromXML')
            ->with(Mockery::type(SimpleXMLElement::class), $this->project, 'name10', 'desc12', 'item11', 'inca-silver', [])
            ->once()->andReturns($tracker_101);
        $this->tracker_xml_importer->shouldReceive('createFromXML')
            ->with(Mockery::type(SimpleXMLElement::class), $this->project, 'name20', 'desc22', 'item21', 'inca-silver', ["T101" => 444])
            ->once()->andReturns($tracker_102);
    }

    private function mockTracker103(): void
    {
        $tracker_103 = Mockery::mock(Tracker::class);
        $tracker_103->shouldReceive('getId')->andReturn(666);
        $this->tracker_xml_importer->shouldReceive('createFromXML')
            ->with(Mockery::type(SimpleXMLElement::class), $this->project, 'name30', 'desc32', 'item31', 'inca-silver', ["T101" => 444, "T102" => 555])
            ->once()->andReturns($tracker_103);
    }
}
