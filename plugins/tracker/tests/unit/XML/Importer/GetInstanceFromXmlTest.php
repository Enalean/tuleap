<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\XML\Importer;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_CannedResponseFactory;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use Tracker_RuleFactory;
use TrackerFactory;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Semantic\TrackerSemanticFactory;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Tracker\XML\Importer\GetInstanceFromXml;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use User\XML\Import\IFindUserFromXMLReference;
use WorkflowFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GetInstanceFromXmlTest extends TestCase
{
    private MockObject&TrackerFactory $tracker_factory;
    private ExternalFieldsExtractor&MockObject $external_fields_extractor;
    private UGroupRetrieverWithLegacy&MockObject $ugroup_retriever_with_legacy;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private TrackerXmlImportFeedbackCollector&MockObject $feedback_collector;
    private ArtifactLinksUsageUpdater&MockObject $artifact_links_usage_updater;
    private MockObject&Tracker_CannedResponseFactory $canned_response_factory;
    private MockObject&IFindUserFromXMLReference $find_user_from_xml_reference;
    private MockObject&TrackerSemanticFactory $semantic_factory;
    private MockObject&Tracker_RuleFactory $rule_factory;
    private MockObject&Tracker_ReportFactory $report_factory;
    private WorkflowFactory&MockObject $workflow_factory;
    private WebhookFactory&MockObject $webhook_factory;
    private NullLogger $logger;
    private \Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_factory              = $this->createMock(TrackerFactory::class);
        $this->external_fields_extractor    = $this->createMock(ExternalFieldsExtractor::class);
        $this->ugroup_retriever_with_legacy = $this->createMock(UGroupRetrieverWithLegacy::class);
        $this->form_element_factory         = $this->createMock(Tracker_FormElementFactory::class);
        $this->feedback_collector           = $this->createMock(TrackerXmlImportFeedbackCollector::class);
        $this->artifact_links_usage_updater = $this->createMock(ArtifactLinksUsageUpdater::class);
        $this->canned_response_factory      = $this->createMock(Tracker_CannedResponseFactory::class);
        $this->find_user_from_xml_reference = $this->createMock(IFindUserFromXMLReference::class);
        $this->semantic_factory             = $this->createMock(TrackerSemanticFactory::class);
        $this->rule_factory                 = $this->createMock(Tracker_RuleFactory::class);
        $this->report_factory               = $this->createMock(Tracker_ReportFactory::class);
        $this->workflow_factory             = $this->createMock(WorkflowFactory::class);
        $this->webhook_factory              = $this->createMock(WebhookFactory::class);
        $this->logger                       = new NullLogger();
        $this->project                      = ProjectTestBuilder::aProject()->withId(123)->build();
    }

    public function testItImportFormElementAndExternalField(): void
    {
        $get_instance_from_xml = $this->getMockBuilder(GetInstanceFromXml::class)
            ->setConstructorArgs([
                $this->tracker_factory,
                $this->canned_response_factory,
                $this->form_element_factory,
                $this->find_user_from_xml_reference,
                $this->feedback_collector,
                $this->semantic_factory,
                $this->rule_factory,
                $this->report_factory,
                $this->workflow_factory,
                $this->webhook_factory,
                $this->ugroup_retriever_with_legacy,
                $this->logger,
            ])
            ->onlyMethods([
                'setTrackerGeneralInformation',
                'setCannedResponses',
                'setSemantics',
                'setLegacyDependencies',
                'setRules',
                'setTrackerReports',
                'setWorkflow',
                'setWebhooks',
                'setPermissions',
            ])
            ->getMock();

        $this->artifact_links_usage_updater->method('forceUsageOfArtifactLinkTypes');
        $this->external_fields_extractor->method('extractExternalFieldFromProjectElement');

        $xml     = new SimpleXMLElement(
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
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([]);

        $this->tracker_factory->expects($this->once())->method('getInstanceFromRow')->willReturn($tracker);
        $this->form_element_factory->expects($this->exactly(2))->method('getInstanceFromXML');

        $get_instance_from_xml->expects($this->once())->method('setTrackerGeneralInformation');
        $get_instance_from_xml->expects($this->once())->method('setCannedResponses');
        $get_instance_from_xml->expects($this->once())->method('setSemantics');
        $get_instance_from_xml->expects($this->once())->method('setLegacyDependencies');
        $get_instance_from_xml->expects($this->once())->method('setRules');
        $get_instance_from_xml->expects($this->once())->method('setTrackerReports');
        $get_instance_from_xml->expects($this->once())->method('setWorkflow');
        $get_instance_from_xml->expects($this->once())->method('setWebhooks');
        $get_instance_from_xml->expects($this->once())->method('setPermissions');

        $xml_fields_mapping    = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $get_instance_from_xml->getInstanceFromXML(
            $xml,
            $this->project,
            'tracker name',
            'tracker description',
            'bugs',
            'peggy-pink',
            [],
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
    }

    public function testGetInstanceFromXmlTryToExtractEverything(): void
    {
        $get_instance_from_xml = $this->getMockBuilder(GetInstanceFromXml::class)
            ->setConstructorArgs([
                $this->tracker_factory,
                $this->canned_response_factory,
                $this->form_element_factory,
                $this->find_user_from_xml_reference,
                $this->feedback_collector,
                $this->semantic_factory,
                $this->rule_factory,
                $this->report_factory,
                $this->workflow_factory,
                $this->webhook_factory,
                $this->ugroup_retriever_with_legacy,
                $this->logger,
            ])
            ->onlyMethods([
                'setTrackerGeneralInformation',
                'setCannedResponses',
                'setSemantics',
                'setLegacyDependencies',
                'setRules',
                'setTrackerReports',
                'setWorkflow',
                'setWebhooks',
                'setPermissions',
            ])
            ->getMock();

        $xml = simplexml_load_string((string) file_get_contents(__DIR__ . '/../../_fixtures/EmptyTracker.xml'));
        if ($xml === false) {
            throw new Exception('Unable to load xml file');
        }

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([]);

        $this->tracker_factory->expects($this->once())->method('getInstanceFromRow')->willReturn($tracker);

        $get_instance_from_xml->expects($this->once())->method('setTrackerGeneralInformation');
        $get_instance_from_xml->expects($this->once())->method('setCannedResponses');
        $get_instance_from_xml->expects($this->once())->method('setSemantics');
        $get_instance_from_xml->expects($this->once())->method('setLegacyDependencies');
        $get_instance_from_xml->expects($this->once())->method('setRules');
        $get_instance_from_xml->expects($this->once())->method('setTrackerReports');
        $get_instance_from_xml->expects($this->once())->method('setWorkflow');
        $get_instance_from_xml->expects($this->once())->method('setWebhooks');
        $get_instance_from_xml->expects($this->once())->method('setPermissions');

        $xml_fields_mapping    = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $get_instance_from_xml->getInstanceFromXML(
            $xml,
            $this->project,
            'tracker name',
            'tracker description',
            'bugs',
            'peggy-pink',
            [],
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
    }

    public function testGetInstanceFromXmlExtractPermissionsFromXml(): void
    {
        $get_instance_from_xml = $this->getMockBuilder(GetInstanceFromXml::class)
            ->setConstructorArgs([
                $this->tracker_factory,
                $this->canned_response_factory,
                $this->form_element_factory,
                $this->find_user_from_xml_reference,
                $this->feedback_collector,
                $this->semantic_factory,
                $this->rule_factory,
                $this->report_factory,
                $this->workflow_factory,
                $this->webhook_factory,
                $this->ugroup_retriever_with_legacy,
                $this->logger,
            ])
            ->onlyMethods([
                'setTrackerGeneralInformation',
                'setCannedResponses',
                'setSemantics',
                'setLegacyDependencies',
                'setRules',
                'setTrackerReports',
                'setWorkflow',
                'setWebhooks',
            ])
            ->getMock();

        $xml = simplexml_load_string((string) file_get_contents(__DIR__ . '/../../_fixtures/PermissionTracker.xml'));
        if ($xml === false) {
            throw new Exception('Unable to load xml file');
        }

        $contributors_ugroup    = $this->createMock(ProjectUGroup::class);
        $contributors_ugroup_id = 42;
        $contributors_ugroup->method('getId')->willReturn($contributors_ugroup_id);

        $this->ugroup_retriever_with_legacy->expects($this->exactly(3))->method('getUGroupId')
            ->willReturnCallback(static fn (Project $project, string $ugroup_name) => match ($ugroup_name) {
                'Contributors' => $contributors_ugroup_id,
                'UGROUP_REGISTERED' => 3,
                'UGROUP_PROJECT_MEMBERS' => 4,
            });

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getName')->willReturn('bugs');
        $tracker->method('getFormElementFields')->willReturn([]);

        $this->tracker_factory->expects($this->once())->method('getInstanceFromRow')->willReturn($tracker);

        $tracker->expects($this->exactly(2))->method('setCachePermission')->willReturnCallback(
            static fn (int $ugroup_id, string $permission_type) => match (true) {
                $permission_type === 'PLUGIN_TRACKER_ACCESS_FULL' && $ugroup_id === $contributors_ugroup_id,
                $permission_type === 'PLUGIN_TRACKER_ACCESS_FULL' && $ugroup_id === 3 => true,
            }
        );

        $field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $this->form_element_factory->method('getInstanceFromXML')->willReturn($field);

        $field->expects($this->once())->method('setCachePermission')->with(4, 'PLUGIN_TRACKER_FIELD_UPDATE');

        $xml_fields_mapping    = [
            'F1685' => $field,
        ];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $get_instance_from_xml->getInstanceFromXML(
            $xml,
            $this->project,
            'tracker name',
            'tracker description',
            'bugs',
            'peggy-pink',
            [],
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
    }

    public function testItExtractGeneralSettingsFromImport(): void
    {
        $get_instance_from_xml = $this->getMockBuilder(GetInstanceFromXml::class)
            ->setConstructorArgs([
                $this->tracker_factory,
                $this->canned_response_factory,
                $this->form_element_factory,
                $this->find_user_from_xml_reference,
                $this->feedback_collector,
                $this->semantic_factory,
                $this->rule_factory,
                $this->report_factory,
                $this->workflow_factory,
                $this->webhook_factory,
                $this->ugroup_retriever_with_legacy,
                $this->logger,
            ])
            ->onlyMethods([
                'setFormElementFields',
                'setCannedResponses',
                'setSemantics',
                'setLegacyDependencies',
                'setRules',
                'setTrackerReports',
                'setWorkflow',
                'setWebhooks',
            ])
            ->getMock();

        $xml = simplexml_load_string((string) file_get_contents(__DIR__ . '/../../_fixtures/TestTracker-1.xml'));
        if ($xml === false) {
            throw new Exception('Unable to load xml file');
        }

        $expected_row = [
            'id'                           => 0,
            'name'                         => 'tracker name',
            'group_id'                     => 123,
            'description'                  => 'tracker description',
            'item_name'                    => 'bugs',
            'submit_instructions'          => 'some submit instructions',
            'browse_instructions'          => 'and some for browsing',
            'status'                       => '',
            'deletion_date'                => '',
            'color'                        => 'peggy-pink',
            'allow_copy'                   => 1,
            'enable_emailgateway'          => 0,
            'instantiate_for_new_projects' => 1,
            'log_priority_changes'         => 0,
            'notifications_level'          => Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
        ];

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([]);
        $this->tracker_factory->expects($this->once())->method('getInstanceFromRow')->with($expected_row)->willReturn($tracker);

        $xml_fields_mapping    = [];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $get_instance_from_xml->getInstanceFromXML(
            $xml,
            $this->project,
            'tracker name',
            'tracker description',
            'bugs',
            'peggy-pink',
            [],
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
    }

    public function testWarnUserIfAFieldHasNoPermission(): void
    {
        $get_instance_from_xml = $this->getMockBuilder(GetInstanceFromXml::class)
            ->setConstructorArgs([
                $this->tracker_factory,
                $this->canned_response_factory,
                $this->form_element_factory,
                $this->find_user_from_xml_reference,
                $this->feedback_collector,
                $this->semantic_factory,
                $this->rule_factory,
                $this->report_factory,
                $this->workflow_factory,
                $this->webhook_factory,
                $this->ugroup_retriever_with_legacy,
                $this->logger,
            ])
            ->onlyMethods([
                'setTrackerGeneralInformation',
                'setFormElementFields',
                'setCannedResponses',
                'setSemantics',
                'setLegacyDependencies',
                'setRules',
                'setTrackerReports',
                'setWorkflow',
                'setWebhooks',
            ])
            ->getMock();

        $xml = new SimpleXMLElement(
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

        $field_1 = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field_1->method('getName')->willReturn('field_1');
        $field_1->method('hasCachedPermissions')->willReturn(true);

        $field_2 = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field_2->method('getName')->willReturn('field_2');
        $field_2->method('hasCachedPermissions')->willReturn(false);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getName')->willReturn('tracker_name');
        $tracker->method('getFormElementFields')->willReturn([
            $field_1,
            $field_2,
        ]);

        $this->tracker_factory->expects($this->once())->method('getInstanceFromRow')->willReturn($tracker);

        $this->feedback_collector->expects($this->once())->method('addWarnings')
            ->with('Tracker tracker_name : field field_2 (F692) has no permission');

        $xml_fields_mapping    = ['F691' => $field_1, 'F692' => $field_2];
        $reports_xml_mapping   = [];
        $renderers_xml_mapping = [];

        $get_instance_from_xml->getInstanceFromXML(
            $xml,
            $this->project,
            'tracker name',
            'tracker description',
            'bugs',
            'peggy-pink',
            [],
            $xml_fields_mapping,
            $reports_xml_mapping,
            $renderers_xml_mapping,
        );
    }
}
