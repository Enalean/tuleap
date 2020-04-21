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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\MappingsRegistry;

require_once __DIR__ . '/../bootstrap.php';


//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TrackerXmlImportArtifactLinkV2Activation extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $configuration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ExternalFieldsExtractor
     */
    private $external_validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hierarchy_dao               = Mockery::spy(HierarchyDAO::class);
        $this->artifact_link_usage_updater = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater::class);
        $this->artifact_link_usage_dao     = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $this->event_manager               = \Mockery::spy(\EventManager::class);
        $this->external_validator          = \Mockery::mock(ExternalFieldsExtractor::class);

        $form_element_factory = new class extends Tracker_FormElementFactory {
            private $mapping = array();

            public function __construct()
            {
                $this->mapping = [];
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
        };

        $this->tracker_xml_importer = new TrackerXmlImport(
            \Mockery::spy(\TrackerFactory::class),
            $this->event_manager,
            $this->hierarchy_dao,
            \Mockery::spy(\Tracker_CannedResponseFactory::class),
            $form_element_factory,
            \Mockery::spy(\Tracker_SemanticFactory::class),
            \Mockery::spy(\Tracker_RuleFactory::class),
            \Mockery::spy(\Tracker_ReportFactory::class),
            \Mockery::spy(\WorkflowFactory::class),
            \Mockery::spy(\XML_RNGValidator::class),
            \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class),
            \Mockery::spy(\Tracker_Artifact_XMLImport::class),
            \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class),
            \Mockery::spy(\UGroupManager::class),
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            $this->artifact_link_usage_updater,
            $this->artifact_link_usage_dao,
            \Mockery::spy(\Tuleap\Tracker\Webhook\WebhookFactory::class),
            \Mockery::spy(\Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker::class),
            $this->external_validator
        );

        $this->external_validator->shouldReceive('extractExternalFieldFromProjectElement');
        $this->external_validator->shouldReceive('extractExternalFieldsFromFormElements');

        $this->project = Mockery::mock(Project::class)->shouldReceive('getId')->andReturn(201)->getMock();

        $this->mapping_registery = new MappingsRegistry();
        $this->configuration     = new ImportConfig();
    }

    public function testItShouldActivateIfNoAttributeAndProjectUsesNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers /></project>');

        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->andReturns(true);
        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->never();
        $this->artifact_link_usage_updater->shouldReceive('forceUsageOfArtifactLinkTypes')->once();
        $this->artifact_link_usage_updater->shouldReceive('forceDeactivationOfArtifactLinkTypes')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldActivateIfNoAttributeAndProjectDoesNotUseNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers /></project>');

        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->andReturns(false);
        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->never();
        $this->artifact_link_usage_updater->shouldReceive('forceUsageOfArtifactLinkTypes')->once();
        $this->artifact_link_usage_updater->shouldReceive('forceDeactivationOfArtifactLinkTypes')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldNotActivateIfAttributeIsFalseAndProjectDoesNotUseNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="false"/></project>');

        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->once()->andReturns(false);
        $this->artifact_link_usage_updater->shouldReceive('forceUsageOfArtifactLinkTypes')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldActivateIfAttributeIsTrueAndProjectDoesNotUseNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="true"/></project>');

        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->once()->andReturns(false);
        $this->artifact_link_usage_updater->shouldReceive('forceUsageOfArtifactLinkTypes')->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldDoNothingIfAttributeIsTrueAndProjectUsesNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="true"/></project>');

        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->andReturns(true);
        $this->artifact_link_usage_updater->shouldReceive('forceUsageOfArtifactLinkTypes')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }

    public function testItShouldDeactivateIfAttributeIsFalseAndProjectUsesNature(): void
    {
        $xml_input = new SimpleXMLElement('<project><trackers use-natures="false"/></project>');

        $this->artifact_link_usage_updater->shouldReceive('isProjectAllowedToUseArtifactLinkTypes')->once()->andReturns(true);
        $this->artifact_link_usage_updater->shouldReceive('forceDeactivationOfArtifactLinkTypes')->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
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

        $this->artifact_link_usage_dao->shouldReceive('disableTypeInProject')->with(201, 'type_name')->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
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

        $this->artifact_link_usage_dao->shouldReceive('disableTypeInProject')->with(201, 'type_name')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
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

        $this->artifact_link_usage_dao->shouldReceive('disableTypeInProject')->with(201, 'type_name')->never();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
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

        $this->event_manager->shouldReceive('processEvent')->with(\Mockery::type(XMLImportArtifactLinkTypeCanBeDisabled::class))->once();

        $this->tracker_xml_importer->import($this->configuration, $this->project, $xml_input, $this->mapping_registery, '');
    }
}
