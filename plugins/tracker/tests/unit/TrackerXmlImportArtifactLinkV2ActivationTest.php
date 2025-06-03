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

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationNotificationsSettingsFromXmlBuilder;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Tracker\XML\Importer\GetInstanceFromXml;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\MappingsRegistry;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerXmlImportArtifactLinkV2ActivationTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private ImportConfig $configuration;
    private ExternalFieldsExtractor&MockObject $external_validator;
    private MappingsRegistry $mappings_registry;
    private TrackerXmlImport $tracker_xml_importer;
    private HierarchyDAO&MockObject $hierarchy_dao;
    private ArtifactLinksUsageUpdater&MockObject $artifact_link_usage_updater;
    private ArtifactLinksUsageDao&MockObject $artifact_link_usage_dao;
    private EventManager&MockObject $event_manager;
    private Project $project;
    private PFUser $user;
    private GetInstanceFromXml&MockObject $get_instance_from_xml;

    protected function setUp(): void
    {
        $this->hierarchy_dao               = $this->createMock(HierarchyDAO::class);
        $this->artifact_link_usage_updater = $this->createMock(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class);
        $this->artifact_link_usage_dao     = $this->createMock(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $this->event_manager               = $this->createMock(\EventManager::class);
        $this->external_validator          = $this->createMock(ExternalFieldsExtractor::class);

        $this->artifact_link_usage_dao->method('isTypeDisabledInProject');

        $form_element_factory = new class extends Tracker_FormElementFactory {
            private $mapping = [];

            public function __construct()
            {
                $this->mapping = [];
            }

            public function getInstanceFromXML(
                Tracker $tracker,
                $elem,
                &$xmlMapping,
                User\XML\Import\IFindUserFromXMLReference $user_finder,
                TrackerXmlImportFeedbackCollector $feedback_collector,
            ) {
                $xmlMapping = $this->mapping;
            }
        };

        $rng_validator = $this->createMock(\XML_RNGValidator::class);
        $rng_validator->method('validate');

        $this->get_instance_from_xml = $this->createMock(GetInstanceFromXml::class);

        $this->tracker_xml_importer = new TrackerXmlImport(
            $this->createMock(\TrackerFactory::class),
            $this->event_manager,
            $this->hierarchy_dao,
            $this->get_instance_from_xml,
            $form_element_factory,
            $rng_validator,
            $this->createMock(\Tracker_Workflow_Trigger_RulesManager::class),
            $this->createMock(\Tracker_Artifact_XMLImport::class),
            $this->createMock(\User\XML\Import\IFindUserFromXMLReference::class),
            new \Psr\Log\NullLogger(),
            $this->artifact_link_usage_updater,
            $this->artifact_link_usage_dao,
            $this->createMock(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class),
            $this->external_validator,
            $this->createMock(TrackerXmlImportFeedbackCollector::class),
            $this->createMock(TrackerCreationDataChecker::class),
            new TrackerCreationNotificationsSettingsFromXmlBuilder(),
        );

        $this->external_validator->method('extractExternalFieldFromProjectElement');

        $this->project = new Project(['group_id' => '201']);

        $this->user = new PFUser(['language_id' => 'en_US']);

        $this->mappings_registry = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function testItShouldActivateIfNoAttributeAndProjectUsesNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers /></project>');

        $this->artifact_link_usage_updater->expects($this->never())->method('isProjectAllowedToUseArtifactLinkTypes');
        $this->artifact_link_usage_updater->expects($this->once())->method('forceUsageOfArtifactLinkTypes');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldActivateIfNoAttributeAndProjectDoesNotUseNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers /></project>');

        $this->artifact_link_usage_updater->expects($this->never())->method('isProjectAllowedToUseArtifactLinkTypes');
        $this->artifact_link_usage_updater->expects($this->once())->method('forceUsageOfArtifactLinkTypes');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldNotActivateIfAttributeIsFalseAndProjectDoesNotUseNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="false"/></project>');

        $this->artifact_link_usage_updater->expects($this->once())->method('isProjectAllowedToUseArtifactLinkTypes')->willReturn(false);
        $this->artifact_link_usage_updater->expects($this->never())->method('forceUsageOfArtifactLinkTypes');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldActivateIfAttributeIsTrueAndProjectDoesNotUseNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="true"/></project>');

        $this->artifact_link_usage_updater->expects($this->once())->method('isProjectAllowedToUseArtifactLinkTypes')->willReturn(false);
        $this->artifact_link_usage_updater->expects($this->once())->method('forceUsageOfArtifactLinkTypes');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldDoNothingIfAttributeIsTrueAndProjectUsesNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="true"/></project>');

        $this->artifact_link_usage_updater->method('isProjectAllowedToUseArtifactLinkTypes')->willReturn(true);
        $this->artifact_link_usage_updater->expects($this->never())->method('forceUsageOfArtifactLinkTypes');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldNotForceActivateIfAttributeIsFalseAndProjectUsesNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="false"/></project>');

        $this->artifact_link_usage_updater->expects($this->once())->method('isProjectAllowedToUseArtifactLinkTypes')->willReturn(true);
        $this->artifact_link_usage_updater->expects($this->never())->method('forceUsageOfArtifactLinkTypes');

        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldDeactivateATypeIfAttributeIsFalse(): void
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature is_used="0">type_name</nature>
                </natures>
            </project>'
        );

        $this->artifact_link_usage_dao->expects($this->once())->method('disableTypeInProject')->with(201, 'type_name');

        $this->artifact_link_usage_updater->method('forceUsageOfArtifactLinkTypes');
        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldActivateATypeIfAttributeIsTrue(): void
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature is_used="true">type_name</nature>
                </natures>
            </project>'
        );

        $this->artifact_link_usage_dao->expects($this->never())->method('disableTypeInProject')->with(201, 'type_name');

        $this->artifact_link_usage_updater->method('forceUsageOfArtifactLinkTypes');
        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItShouldActivateATypeIfAttributeIsMissing(): void
    {
        $xml_input = new SimpleXMLElement(
            '<project>
                <trackers/>
                <natures>
                    <nature>type_name</nature>
                </natures>
            </project>'
        );

        $this->artifact_link_usage_dao->expects($this->never())->method('disableTypeInProject')->with(201, 'type_name');

        $this->artifact_link_usage_updater->method('forceUsageOfArtifactLinkTypes');
        $this->event_manager->method('processEvent');

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);
    }

    public function testItThrowsAnEventToCheckIfTypeCanBeDisabled(): void
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
        $this->artifact_link_usage_dao->method('disableTypeInProject');
        $this->artifact_link_usage_updater->method('forceUsageOfArtifactLinkTypes');

        $is_disabled_called = false;
        $this->event_manager->method('processEvent')->willReturnCallback(
            static function (mixed $event) use (&$is_disabled_called) {
                if ($event instanceof XMLImportArtifactLinkTypeCanBeDisabled) {
                    $is_disabled_called = true;
                }
                return $event;
            }
        );

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mappings_registry, '', $this->user);

        self::assertTrue($is_disabled_called);
    }
}
