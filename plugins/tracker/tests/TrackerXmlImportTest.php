<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

require_once 'bootstrap.php';

use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\Tracker\XML\Importer\TrackerExtraConfiguration;
use Tuleap\XML\MappingsRegistry;
use Tuleap\Project\XML\Import\ImportConfig;

class TrackerXmlImportTestInstance extends TrackerXmlImport
{

    public function getInstanceFromXML(SimpleXMLElement $xml, Project $project, $name, $description, $itemname, TrackerXmlImportFeedbackCollector $feedback_collector)
    {
        return parent::getInstanceFromXML($xml, $project, $name, $description, $itemname, $feedback_collector);
    }

    public function getAllXmlTrackers(SimpleXMLElement $xml)
    {
        return parent::getAllXmlTrackers($xml);
    }

    public function buildTrackersHierarchy(array $hierarchy, SimpleXMLElement $xml_tracker, array $mapper)
    {
        return parent::buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);
    }
}

class TrackerXmlImportTest extends TuleapTestCase
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    private $group_id = 145;

    /**
     * @var TrackerXmlImportTestInstance
     */
    private $tracker_xml_importer;
    private $extraction_path;
    private $configuration;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->extraction_path = '';

        $this->xml_input =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
              <trackers>
                  <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                  </tracker>
                  <tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                  </tracker>
                  <tracker id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>name30</name>
                    <item_name>item31</item_name>
                    <description>desc32</description>
                  </tracker>
              </trackers>
              <cardwall/>
              <agiledashboard/>
            </project>');

        $this->group_id = 145;
        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getID()->returns($this->group_id);

        $this->xml_tracker1 = new SimpleXMLElement(
            '<tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                  </tracker>'
        );

        $this->xml_tracker2 = new SimpleXMLElement(
            '<tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                  </tracker>'
        );

        $this->xml_tracker3 = new SimpleXMLElement(
            '<tracker id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>name30</name>
                    <item_name>item31</item_name>
                    <description>desc32</description>
                  </tracker>'
        );

        $this->xml_trackers_list = array("T101" => $this->xml_tracker1, "T102" => $this->xml_tracker2, "T103" => $this->xml_tracker3);
        $this->mapping = array(
            "T101" => 444,
            "T102" => 555,
            "T103" => 666
        );

        $this->tracker1 = aTracker()->withId(444)->build();
        $this->tracker2 = aTracker()->withId(555)->build();
        $this->tracker3 = aTracker()->withId(666)->build();

        $this->tracker_factory                = \Mockery::spy(\TrackerFactory::class);
        $this->event_manager                  = \Mockery::spy(\EventManager::class);
        $this->hierarchy_dao                  = Mockery::spy(HierarchyDAO::class);
        $this->xml_import                     = \Mockery::spy(\Tracker_Artifact_XMLImport::class);
        $this->ugroup_manager                 = \Mockery::spy(\UGroupManager::class);
        $this->logger                         = \Mockery::spy(\Logger::class);
        $this->formelement_factory            = \Mockery::spy(Tracker_FormElementFactory::class);
        $this->existing_tracker_field_mapping = \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class);

        $class_parameters = array(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(Tracker_CannedResponseFactory::class),
            $this->formelement_factory,
            \Mockery::spy(Tracker_SemanticFactory::class),
            \Mockery::spy(Tracker_RuleFactory::class),
            \Mockery::spy(Tracker_ReportFactory::class),
            \Mockery::spy(WorkflowFactory::class),
            \Mockery::spy(XML_RNGValidator::class),
            \Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class),
            $this->xml_import,
            \Mockery::spy(User\XML\Import\IFindUserFromXMLReference::class),
            $this->ugroup_manager,
            $this->logger,
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            $this->existing_tracker_field_mapping
        );

        $this->tracker_xml_importer = \Mockery::mock(\TrackerXmlImportTestInstance::class, $class_parameters)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->mapping_registery = new MappingsRegistry();

        $this->configuration = new ImportConfig();
    }

    public function itReturnsEachSimpleXmlTrackerFromTheXmlInput()
    {
        $trackers_result = $this->tracker_xml_importer->getAllXmlTrackers($this->xml_input);
        $diff = array_diff($trackers_result, $this->xml_trackers_list);

        $this->assertEqual(count($trackers_result), 3);
        $this->assertTrue(empty($diff));
    }

    public function itCreatesAllTrackersAndStoresTrackersHierarchy()
    {
        stub($this->tracker_xml_importer)->createFromXML(\Mockery::type(SimpleXMLElement::class), $this->project, 'name10', 'desc12', 'item11')->once()->returns($this->tracker1);
        stub($this->tracker_xml_importer)->createFromXML(\Mockery::type(SimpleXMLElement::class), $this->project, 'name20', 'desc22', 'item21')->once()->returns($this->tracker2);
        stub($this->tracker_xml_importer)->createFromXML(\Mockery::type(SimpleXMLElement::class), $this->project, 'name30', 'desc32', 'item31')->once()->returns($this->tracker3);

        expect($this->hierarchy_dao)->updateChildren(2);

        $result = $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );

        $this->assertEqual($result, $this->mapping);
    }

    public function itReUsesTrackerAtTrackerImport()
    {
        $extra_configuration = new TrackerExtraConfiguration(['item31']);
        $this->configuration->addExtraConfiguration($extra_configuration);

        stub($this->tracker_xml_importer)->createFromXML(\Mockery::type(SimpleXMLElement::class), $this->project, 'name10', 'desc12', 'item11')->once()->returns($this->tracker1);
        stub($this->tracker_xml_importer)->createFromXML(\Mockery::type(SimpleXMLElement::class), $this->project, 'name20', 'desc22', 'item21')->once()->returns($this->tracker2);
        stub($this->tracker_xml_importer)->createFromXML(\Mockery::type(SimpleXMLElement::class), $this->project, 'name30', 'desc32', 'item31')->never();
        stub($this->tracker_factory)->getTrackerByShortnameAndProjectId('item31', $this->group_id)->once()->returns($this->tracker3);
        stub($this->formelement_factory)->getFields($this->tracker3)->once()->returns([]);
        stub($this->existing_tracker_field_mapping)->getXmlFieldsMapping()->once()->returns([]);

        expect($this->hierarchy_dao)->updateChildren(2);

        $result = $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );

        $this->assertEqual($result, $this->mapping);
    }

    public function itRaisesAnExceptionIfATrackerCannotBeCreatedAndDoesNotContinue()
    {
        stub($this->tracker_xml_importer)->createFromXML()->returns(null);

        $this->expectException();
        expect($this->tracker_xml_importer)->createFromXML()->count(1);
        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );
    }

    public function itThrowsAnEventIfAllTrackersAreCreated()
    {
        stub($this->tracker_xml_importer)->createFromXML(
            \Mockery::type(SimpleXMLElement::class),
            $this->project,
            'name10',
            'desc12',
            'item11'
        )->once()->returns($this->tracker1);

        stub($this->tracker_xml_importer)->createFromXML(
            \Mockery::type(SimpleXMLElement::class),
            $this->project,
            'name20',
            'desc22',
            'item21'
        )->once()->returns($this->tracker2);

        stub($this->tracker_xml_importer)->createFromXML(
            \Mockery::type(SimpleXMLElement::class),
            $this->project,
            'name30',
            'desc32',
            'item31'
        )->once()->returns($this->tracker3);

        expect($this->event_manager)->processEvent(
            Event::IMPORT_XML_PROJECT_TRACKER_DONE,
            \Mockery::on(function (array $parameters) {
                return $parameters['project'] === $this->project &&
                    $parameters['xml_content'] === $this->xml_input &&
                    $parameters['mapping'] === $this->mapping &&
                    $parameters['field_mapping'] === [] &&
                    $parameters['mappings_registery'] === $this->mapping_registery &&
                    is_a($parameters['artifact_id_mapping'], Tracker_XML_Importer_ArtifactImportedMapping::class) &&
                    $parameters['extraction_path'] === $this->extraction_path &&
                    $parameters['logger'] === $this->logger &&
                    is_a($parameters['value_mapping'], TrackerXmlFieldsMapping_FromAnotherPlatform::class);
            })
        )->once();

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );
    }

    public function itBuildsTrackersHierarchy()
    {
        $hierarchy = array();
        $expected_hierarchy = array(444 => array(555));
        $mapper = array("T101" => 444, "T102" => 555);
        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $this->xml_tracker2, $mapper);

        $this->assertTrue(! empty($hierarchy));
        $this->assertNotNull($hierarchy[444]);
        $this->assertIdentical($hierarchy, $expected_hierarchy);
    }

    public function itAddsTrackersHierarchyOnExistingHierarchy()
    {
        $hierarchy          = array(444 => array(555));
        $expected_hierarchy = array(444 => array(555, 666));
        $mapper             = array("T101" => 444, "T103" => 666);
        $xml_tracker        = new SimpleXMLElement(
            '<tracker id="T103" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);

        $this->assertTrue(! empty($hierarchy));
        $this->assertNotNull($hierarchy[444]);
        $this->assertIdentical($expected_hierarchy, $hierarchy);
    }

    public function itCollectsErrorsWithoutImporting()
    {
        $class_parameters = array(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(Tracker_CannedResponseFactory::class),
            \Mockery::spy(Tracker_FormElementFactory::class),
            \Mockery::spy(Tracker_SemanticFactory::class),
            \Mockery::spy(Tracker_RuleFactory::class),
            \Mockery::spy(Tracker_ReportFactory::class),
            \Mockery::spy(WorkflowFactory::class),
            \Mockery::spy(XML_RNGValidator::class),
            \Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class),
            $this->xml_import,
            \Mockery::spy(User\XML\Import\IFindUserFromXMLReference::class),
            $this->ugroup_manager,
            \Mockery::spy(Logger::class),
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $tracker_xml_importer = \Mockery::mock(\TrackerXmlImportTestInstance::class, $class_parameters)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        expect($tracker_xml_importer)->getInstanceFromXML()->count(3);
        expect($tracker_xml_importer)->import()->never();
        expect($this->tracker_factory)->collectTrackersNameInErrorOnMandatoryCreationInfo()->once();

        $collected_errors = $tracker_xml_importer->collectErrorsWithoutImporting($this->project, $this->xml_input);
        $this->assertEqual($collected_errors, '');
    }

    public function itSouldNotImportHierarchyIfIsChildIsNotUsed()
    {
        $artifact_links_usage_dao = Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $artifact_links_usage_dao->shouldReceive('isTypeDisabledInProject')
            ->with(Mockery::any(), '_is_child')->andReturn(true);

        $class_parameters = array(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(Tracker_CannedResponseFactory::class),
            \Mockery::spy(Tracker_FormElementFactory::class),
            \Mockery::spy(Tracker_SemanticFactory::class),
            \Mockery::spy(Tracker_RuleFactory::class),
            \Mockery::spy(Tracker_ReportFactory::class),
            \Mockery::spy(WorkflowFactory::class),
            \Mockery::spy(XML_RNGValidator::class),
            \Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class),
            $this->xml_import,
            \Mockery::spy(User\XML\Import\IFindUserFromXMLReference::class),
            $this->ugroup_manager,
            \Mockery::spy(Logger::class),
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            $artifact_links_usage_dao,
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $tracker_xml_importer = \Mockery::mock(\TrackerXmlImportTestInstance::class, $class_parameters)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        stub($tracker_xml_importer)->createFromXML()->returns($this->tracker1);

        $this->hierarchy_dao->shouldReceive('updateChildren')->never();

        $tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );
    }
}

class TrackerXmlImport_WithArtifactsTest extends TuleapTestCase
{
    private $configuration;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->extraction_path = '';

        $this->xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
              <trackers>
                  <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                    <artifacts/>
                  </tracker>
              </trackers>
            </project>');

        $this->group_id = 145;
        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns($this->group_id);

        $this->tracker         = \Mockery::spy(\Tracker::class);
        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $this->event_manager   = \Mockery::spy(\EventManager::class);
        $this->hierarchy_dao   = Mockery::spy(HierarchyDAO::class);
        $this->xml_import      = \Mockery::spy(\Tracker_Artifact_XMLImport::class);
        $this->ugroup_manager  = \Mockery::spy(\UGroupManager::class);

        $class_parameters = array(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(Tracker_CannedResponseFactory::class),
            \Mockery::spy(Tracker_FormElementFactory::class),
            \Mockery::spy(Tracker_SemanticFactory::class),
            \Mockery::spy(Tracker_RuleFactory::class),
            \Mockery::spy(Tracker_ReportFactory::class),
            \Mockery::spy(WorkflowFactory::class),
            \Mockery::spy(XML_RNGValidator::class),
            \Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class),
            $this->xml_import,
            \Mockery::spy(User\XML\Import\IFindUserFromXMLReference::class),
            $this->ugroup_manager,
            \Mockery::spy(Logger::class),
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $this->tracker_xml_importer = \Mockery::mock(\TrackerXmlImportTestInstance::class, $class_parameters)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->mapping_registery = new MappingsRegistry();

        $this->configuration = new ImportConfig();
    }

    public function itImportsArtifacts()
    {
        stub($this->tracker_xml_importer)->createFromXML()->returns($this->tracker);
        $this->xml_import->shouldReceive('importBareArtifactsFromXML')->once()->andReturn([]);
        $this->xml_import->shouldReceive('importArtifactChangesFromXML')->once();

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );
    }

    public function itImportsUpdatedArtifacts()
    {
        stub($this->tracker_xml_importer)->updateFromXML()->returns($this->tracker);
        $this->xml_import->shouldReceive('importBareArtifactsFromXML')->andReturn([]);
        $this->configuration->setUpdate(true);
        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );
    }
}

class TrackerXmlImport_InstanceTest extends TuleapTestCase
{
    /** @var TrackerXmlImportTest */
    private $tracker_xml_importer;
    private $xml_security;

    /**
     * @var \Mockery\MockInterface|TrackerXmlImportFeedbackCollector
     */
    private $error_logger;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $tracker_factory = \Mockery::mock(\TrackerFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->error_logger = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $this->tracker_xml_importer = new TrackerXmlImportTestInstance(
            $tracker_factory,
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(HierarchyDAO::class),
            \Mockery::spy(\Tracker_CannedResponseFactory::class),
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\Tracker_SemanticFactory::class),
            \Mockery::spy(\Tracker_RuleFactory::class),
            \Mockery::spy(\Tracker_ReportFactory::class),
            \Mockery::spy(\WorkflowFactory::class),
            \Mockery::spy(\XML_RNGValidator::class),
            \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class),
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            \Mockery::spy(\UGroupManager::class),
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns(0);
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testImport()
    {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/TestTracker-1.xml');
        $tracker = $this->tracker_xml_importer->getInstanceFromXML($xml, $this->project, '', '', '', $this->error_logger);

        //testing general properties
        $this->assertEqual($tracker->submit_instructions, 'some submit instructions');
        $this->assertEqual($tracker->browse_instructions, 'and some for browsing');

        $this->assertEqual($tracker->getColor()->getName(), 'inca-silver');

        //testing default values
        $this->assertEqual($tracker->allow_copy, 0);
        $this->assertEqual($tracker->instantiate_for_new_projects, 1);
        $this->assertEqual($tracker->log_priority_changes, 0);
        $this->assertEqual($tracker->getNotificationsLevel(), Tracker::NOTIFICATIONS_LEVEL_DEFAULT);
    }
}

class TrackerFactoryInstanceFromXMLTest extends TuleapTestCase
{

    public function testGetInstanceFromXmlGeneratesRulesFromDependencies()
    {

        $data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tracker />
XML;
        $xml = new SimpleXMLElement($data);
        $xml->addChild('cannedResponses');
        $xml->addChild('formElements');

        $groupId     = 15;
        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns($groupId);

        $name        = 'the tracker';
        $description = 'tracks stuff';
        $itemname    = 'the item';

        $rule_factory = \Mockery::spy(\Tracker_RuleFactory::class);
        $tracker      = \Mockery::spy(\Tracker::class);

        $error_logger = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);

        $tracker_xml_importer = new TrackerXmlImportTestInstance(
            mockery_stub(\TrackerFactory::class)->getInstanceFromRow()->returns($tracker),
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(HierarchyDAO::class),
            \Mockery::spy(\Tracker_CannedResponseFactory::class),
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\Tracker_SemanticFactory::class),
            $rule_factory,
            \Mockery::spy(\Tracker_ReportFactory::class),
            \Mockery::spy(\WorkflowFactory::class),
            \Mockery::spy(\XML_RNGValidator::class),
            \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class),
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            \Mockery::spy(\UGroupManager::class),
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        //create data passed
        $dependencies = $xml->addChild('dependencies');
        $rule = $dependencies->addChild('rule');
        $rule->addChild('source_field')->addAttribute('REF', 'F1');
        $rule->addChild('target_field')->addAttribute('REF', 'F2');
        $rule->addChild('source_value')->addAttribute('REF', 'F3');
        $rule->addChild('target_value')->addAttribute('REF', 'F4');

        //create data expected
        $expected_xml = new SimpleXMLElement($data);
        $expected_rules = $expected_xml->addChild('rules');
        $list_rules = $expected_rules->addChild('list_rules');
        $expected_rule = $list_rules->addChild('rule');
        $expected_rule->addChild('source_field')->addAttribute('REF', 'F1');
        $expected_rule->addChild('target_field')->addAttribute('REF', 'F2');
        $expected_rule->addChild('source_value')->addAttribute('REF', 'F3');
        $expected_rule->addChild('target_value')->addAttribute('REF', 'F4');

        //this is where we check the data has been correctly transformed
        stub($rule_factory)->getInstanceFromXML(
            \Mockery::on(
                function (SimpleXMLElement $expected_rule) {
                    return (string) $expected_rule->list_rules->rule->source_field['REF'] === 'F1' &&
                    (string) $expected_rule->list_rules->rule->target_field['REF'] === 'F2' &&
                    (string) $expected_rule->list_rules->rule->source_value['REF'] === 'F3' &&
                    (string) $expected_rule->list_rules->rule->target_value['REF'] === 'F4';
                }
            ),
            array(),
            $tracker
        )->once();

        $tracker_xml_importer->getInstanceFromXML($xml, $this->project, $name, $description, $itemname, $error_logger);
    }
}

class Tracker_FormElementFactoryForXMLTests extends Tracker_FormElementFactory
{
    private $mapping = array();

    /**
     * @var \Mockery\MockInterface|TrackerXmlImportFeedbackCollector
     */
    private $error_logger;

    public function __construct($mapping)
    {
        $this->mapping      = $mapping;
        $this->error_logger = \Mockery::mock(TrackerXmlImportFeedbackCollector::class);
    }

    public function getInstanceFromXML(
        Tracker $tracker,
        $elem,
        &$xmlMapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        TrackerXmlImportFeedbackCollector $feedback_collector
    ) {
        $xmlMapping = $this->mapping;
    }
}

class TrackerXmlImport_TriggersTest extends TuleapTestCase
{

    private $xml_input;
    private $group_id = 145;
    private $tracker_factory;
    private $event_manager;
    private $hierarchy_dao;
    private $tracker_xml_importer;
    private $trigger_rulesmanager;
    private $xmlFieldMapping;
    private $configuration;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->extraction_path = '';

        $this->xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <empty_section />
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name>t10</name>
                        <item_name>t11</item_name>
                        <description>t12</description>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="1">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2059" label="To be done" is_hidden="0"/>
                                        <item ID="V2060" label="On going" is_hidden="0"/>
                                        <item ID="V2061" label="Done" is_hidden="0"/>
                                        <item ID="V2062" label="Canceled" is_hidden="0"/>
                                        <item ID="V2063" label="Functional review" is_hidden="0"/>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2059"/>
                                    </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                    </tracker>
                    <tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                        <name>t20</name>
                        <item_name>t21</item_name>
                        <description>t22</description>
                        <formElements>
                            <formElement type="sb" ID="F1741" rank="0" required="1">
                              <name>status</name>
                              <label><![CDATA[Status]]></label>
                              <bind type="static" is_rank_alpha="0">
                                <items>
                                  <item ID="V2116" label="To be done" is_hidden="0"/>
                                  <item ID="V2117" label="On going" is_hidden="0"/>
                                  <item ID="V2118" label="Done" is_hidden="0"/>
                                  <item ID="V2119" label="Canceled" is_hidden="0"/>
                                </items>
                                <decorators>
                                  <decorator REF="V2117" r="102" g="102" b="0"/>
                                </decorators>
                                <default_values>
                                  <value REF="V2116"/>
                                </default_values>
                              </bind>
                            </formElement>
                        </formElements>
                    </tracker>
                    <triggers>
                        <trigger_rule>
                          <triggers>
                            <trigger>
                              <field_id REF="F1685"/>
                              <field_value_id REF="V2060"/>
                            </trigger>
                          </triggers>
                          <condition>at_least_one</condition>
                          <target>
                            <field_id REF="F1741"/>
                            <field_value_id REF="V2117"/>
                          </target>
                        </trigger_rule>
                        <trigger_rule>
                          <triggers>
                            <trigger>
                              <field_id REF="F1685"/>
                              <field_value_id REF="V2061"/>
                            </trigger>
                          </triggers>
                          <condition>all_of</condition>
                          <target>
                            <field_id REF="F1741"/>
                            <field_value_id REF="V2118"/>
                          </target>
                        </trigger_rule>
                    </triggers>
                </trackers>
                <cardwall/>
                <agiledashboard/>
            </project>');

        $this->triggers = new SimpleXMLElement('<triggers>
                            <trigger_rule>
                              <triggers>
                                <trigger>
                                  <field_id REF="F1685"/>
                                  <field_value_id REF="V2060"/>
                                </trigger>
                              </triggers>
                              <condition>at_least_one</condition>
                              <target>
                                <field_id REF="F1741"/>
                                <field_value_id REF="V2117"/>
                              </target>
                            </trigger_rule>
                            <trigger_rule>
                              <triggers>
                                <trigger>
                                  <field_id REF="F1685"/>
                                  <field_value_id REF="V2061"/>
                                </trigger>
                              </triggers>
                              <condition>all_of</condition>
                              <target>
                                <field_id REF="F1741"/>
                                <field_value_id REF="V2118"/>
                              </target>
                            </trigger_rule>
                        </triggers>');

        $this->tracker1 = aMockeryTracker()->withId(444)->build();
        stub($this->tracker1)->testImport()->returns(true);

        $this->tracker2 = aMockeryTracker()->withId(555)->build();
        stub($this->tracker2)->testImport()->returns(true);

        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        stub($this->tracker_factory)->validMandatoryInfoOnCreate()->returns(true);
        stub($this->tracker_factory)->getInstanceFromRow()->returns($this->tracker1);
        stub($this->tracker_factory)->getInstanceFromRow()->returns($this->tracker2);
        stub($this->tracker_factory)->saveObject()->returns(444);
        stub($this->tracker_factory)->saveObject()->returns(555);

        $this->event_manager = \Mockery::spy(\EventManager::class);

        $this->hierarchy_dao = Mockery::spy(HierarchyDAO::class);

        $this->xmlFieldMapping = array(
            'F1685' => '',
            'F1741' => '',
            'V2060' => '',
            'V2061' => '',
            'V2117' => '',
            'V2118' => '',
        );

        $this->trigger_rulesmanager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);

        $this->tracker_xml_importer = new TrackerXmlImport(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(\Tracker_CannedResponseFactory::class),
            new Tracker_FormElementFactoryForXMLTests($this->xmlFieldMapping),
            \Mockery::spy(\Tracker_SemanticFactory::class),
            \Mockery::spy(\Tracker_RuleFactory::class),
            \Mockery::spy(\Tracker_ReportFactory::class),
            \Mockery::spy(\WorkflowFactory::class),
            \Mockery::spy(\XML_RNGValidator::class),
            $this->trigger_rulesmanager,
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            \Mockery::spy(\UGroupManager::class),
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns($this->group_id);

        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function itDelegatesToRulesManager()
    {
        expect($this->trigger_rulesmanager)->createFromXML(
            \Mockery::on(
                function (SimpleXMLElement $trigger) {
                    return count($trigger->trigger_rule) === count($this->triggers->trigger_rule) &&
                    (string)$trigger->trigger_rule[0]->triggers->trigger->field_id['REF'] ===
                        (string)$this->triggers->trigger_rule[0]->triggers->trigger->field_id['REF'] &&
                        (string)$trigger->trigger_rule[1]->triggers->trigger->field_id['REF'] ===
                        (string)$this->triggers->trigger_rule[1]->triggers->trigger->field_id['REF'];
                }
            ),
            $this->xmlFieldMapping
        )->once();

        $this->tracker_xml_importer->import(
            $this->configuration,
            $this->project,
            $this->xml_input,
            $this->mapping_registery,
            $this->extraction_path
        );
    }
}

class TrackerXmlImport_PermissionsTest extends TuleapTestCase
{
    private $configuration;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->old_global = $GLOBALS;
        $GLOBALS['UGROUPS'] = array();
        $GLOBALS['UGROUPS']['UGROUP_REGISTERED'] = 3;
        $GLOBALS['UGROUPS']['UGROUP_PROJECT_MEMBERS'] = 4;

        $this->tracker = aMockeryTracker()->withId(444)->build();
        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);

        $this->field1685 = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->xmlFieldMapping = array(
            'F1685' => $this->field1685,
            'F1741' => '',
            'V2060' => '',
            'V2061' => '',
            'V2117' => '',
            'V2118' => '',
        );

        $this->hierarchy_dao = Mockery::spy(HierarchyDAO::class);

        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);

        $this->tracker_xml_importer = new TrackerXmlImport(
            $this->tracker_factory,
            \Mockery::spy(\EventManager::class),
            $this->hierarchy_dao,
            \Mockery::spy(\Tracker_CannedResponseFactory::class),
            new Tracker_FormElementFactoryForXMLTests($this->xmlFieldMapping),
            \Mockery::spy(\Tracker_SemanticFactory::class),
            \Mockery::spy(\Tracker_RuleFactory::class),
            \Mockery::spy(\Tracker_ReportFactory::class),
            \Mockery::spy(\WorkflowFactory::class),
            \Mockery::spy(\XML_RNGValidator::class),
            \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class),
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            $this->ugroup_manager,
            \Mockery::spy(\Logger::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class),
            \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class),
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $this->group_id = 123;
        $this->project = \Mockery::spy(\Project::class);
        stub($this->project)->getId()->returns($this->group_id);
        $this->contributors_ugroup = \Mockery::spy(\ProjectUGroup::class);
        $this->contributors_ugroup_id = 42;
        stub($this->contributors_ugroup)->getId()->returns($this->contributors_ugroup_id);

        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function tearDown()
    {
        parent::tearDown();
        $GLOBALS = $this->old_global;
    }

    public function itShouldImportPermissions()
    {
        $xml = <<<XML
            <project>
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name>t10</name>
                        <item_name>t11</item_name>
                        <description>t12</description>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="1">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2059" label="To be done" is_hidden="0"/>
                                        <item ID="V2060" label="On going" is_hidden="0"/>
                                        <item ID="V2061" label="Done" is_hidden="0"/>
                                        <item ID="V2062" label="Canceled" is_hidden="0"/>
                                        <item ID="V2063" label="Functional review" is_hidden="0"/>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2059"/>
                                    </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                        <permissions>
                            <permission scope="tracker" ugroup="Contributors" type="PLUGIN_TRACKER_ACCESS_FULL"/>
                            <permission scope="tracker" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_ACCESS_FULL"/>
                            <permission scope="field" REF="F1685" ugroup="UGROUP_PROJECT_MEMBERS" type="PLUGIN_TRACKER_FIELD_UPDATE"/>
                        </permissions>
                    </tracker>
                </trackers>
            </project>
XML;
        $xml_input = new SimpleXMLElement($xml);
        stub($this->tracker)->testImport()->returns(true);
        stub($this->tracker_factory)->validMandatoryInfoOnCreate()->returns(true);
        stub($this->tracker_factory)->getInstanceFromRow()->returns($this->tracker);
        stub($this->tracker_factory)->saveObject()->returns(444);

        stub($this->ugroup_manager)->getUGroupByName($this->project, 'Contributors')->returns($this->contributors_ugroup);

        expect($this->tracker)->setCachePermission($this->contributors_ugroup_id, 'PLUGIN_TRACKER_ACCESS_FULL')->once();
        expect($this->tracker)->setCachePermission(3, 'PLUGIN_TRACKER_ACCESS_FULL')->once();
        expect($this->field1685)->setCachePermission(4, 'PLUGIN_TRACKER_FIELD_UPDATE')->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }
}

class TrackerXmlImport_ArtifactLinkV2Activation extends TuleapTestCase
{
    private $configuration;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->hierarchy_dao               = Mockery::spy(HierarchyDAO::class);
        $this->artifact_link_usage_updater = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class);
        $this->artifact_link_usage_dao     = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $this->event_manager               = \Mockery::spy(\EventManager::class);

        $this->tracker_xml_importer = new TrackerXmlImport(
            \Mockery::spy(\TrackerFactory::class),
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(\Tracker_CannedResponseFactory::class),
            new Tracker_FormElementFactoryForXMLTests(array()),
            \Mockery::spy(\Tracker_SemanticFactory::class),
            \Mockery::spy(\Tracker_RuleFactory::class),
            \Mockery::spy(\Tracker_ReportFactory::class),
            \Mockery::spy(\WorkflowFactory::class),
            \Mockery::spy(\XML_RNGValidator::class),
            \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class),
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            \Mockery::spy(\UGroupManager::class),
            \Mockery::spy(\Logger::class),
            $this->artifact_link_usage_updater,
            $this->artifact_link_usage_dao,
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class)
        );

        $this->project = aMockProject()->withId(201)->build();

        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function itShouldActivateIfNoAttributeAndProjectUsesNature()
    {
        $xml_input = new SimpleXMLElement('<project><trackers /></project>');

        stub($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->returns(true);
        expect($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->never();
        expect($this->artifact_link_usage_updater)->forceUsageOfArtifactLinkTypes()->once();
        expect($this->artifact_link_usage_updater)->forceDeactivationOfArtifactLinkTypes()->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldActivateIfNoAttributeAndProjectDoesNotUseNature()
    {
        $xml_input = new SimpleXMLElement('<project><trackers /></project>');

        stub($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->returns(false);
        expect($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->never();
        expect($this->artifact_link_usage_updater)->forceUsageOfArtifactLinkTypes()->once();
        expect($this->artifact_link_usage_updater)->forceDeactivationOfArtifactLinkTypes()->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldNotActivateIfAttributeIsFalseAndProjectDoesNotUseNature()
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="false"/></project>');

        stub($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->once()->returns(false);
        expect($this->artifact_link_usage_updater)->forceUsageOfArtifactLinkTypes()->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldActivateIfAttributeIsTrueAndProjectDoesNotUseNature()
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="true"/></project>');

        stub($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->once()->returns(false);
        expect($this->artifact_link_usage_updater)->forceUsageOfArtifactLinkTypes()->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldDoNothingIfAttributeIsTrueAndProjectUsesNature()
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="true"/></project>');

        stub($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->returns(true);
        expect($this->artifact_link_usage_updater)->forceUsageOfArtifactLinkTypes()->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldDeactivateIfAttributeIsFalseAndProjectUsesNature()
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="false"/></project>');

        stub($this->artifact_link_usage_updater)->isProjectAllowedToUseArtifactLinkTypes()->once()->returns(true);
        expect($this->artifact_link_usage_updater)->forceDeactivationOfArtifactLinkTypes()->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldDeactivateATypeIfAttributeIsFalse()
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature is_used="0">type_name</nature>
                </natures>
            </project>'
        );

        expect($this->artifact_link_usage_dao)->disableTypeInProject(201, 'type_name')->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldActivateATypeIfAttributeIsTrue()
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature is_used="true">type_name</nature>
                </natures>
            </project>'
        );

        expect($this->artifact_link_usage_dao)->disableTypeInProject(201, 'type_name')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itShouldActivateATypeIfAttributeIsMissing()
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature>type_name</nature>
                </natures>
            </project>'
        );

        expect($this->artifact_link_usage_dao)->disableTypeInProject(201, 'type_name')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function itThrowsAnEventToCheckIfTypeCanBeDisabled()
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature is_used="0">type_name</nature>
                    <nature>type2</nature>
                    <nature is_used="1">type3</nature>
                </natures>
            </project>'
        );

        expect($this->event_manager)->processEvent(\Mockery::type(XMLImportArtifactLinkTypeCanBeDisabled::class))->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }
}
