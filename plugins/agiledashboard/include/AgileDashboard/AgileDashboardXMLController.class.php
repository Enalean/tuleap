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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\AgileDashboard\Milestone\Sidebar\MilestonesInSidebarXmlImport;
use Tuleap\XML\SimpleXMLElementBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\XMLImporter;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;

/**
 * Handles the HTTP actions related to  the agile dashborad as a whole.
 *
 */
class AgileDashboard_XMLController extends MVC2_PluginController
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var XML_RNGValidator
     */
    private $xml_rng_validator;

    /**
     * @var AgileDashboard_XMLExporter
     */
    private $agiledashboard_xml_exporter;

    /**
     * @var AgileDashboard_XMLImporter
     */
    private $agiledashboard_xml_importer;

    /**
     * @var Planning_RequestValidator
     */
    private $planning_request_validator;
    /**
     * @var XMLImporter
     */
    private $explicit_backlog_xml_import;

    /**
     * @var ExternalFieldsExtractor
     */
    private $external_field_extractor;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        XML_RNGValidator $xml_rng_validator,
        AgileDashboard_XMLExporter $agiledashboard_xml_exporter,
        AgileDashboard_XMLImporter $agiledashboard_xml_importer,
        Planning_RequestValidator $planning_request_validator,
        XMLImporter $explicit_backlog_xml_import,
        ExternalFieldsExtractor $external_field_extractor,
        EventDispatcherInterface $event_dispatcher,
        private readonly MilestonesInSidebarXmlImport $milestones_in_sidebar_xml_import,
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id                    = $request->getValidated('project_id', 'uint');
        $this->planning_factory            = $planning_factory;
        $this->xml_rng_validator           = $xml_rng_validator;
        $this->agiledashboard_xml_exporter = $agiledashboard_xml_exporter;
        $this->agiledashboard_xml_importer = $agiledashboard_xml_importer;
        $this->planning_request_validator  = $planning_request_validator;
        $this->explicit_backlog_xml_import = $explicit_backlog_xml_import;
        $this->external_field_extractor    = $external_field_extractor;
        $this->event_dispatcher            = $event_dispatcher;
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    public function export()
    {
        $root_node = $this->request->get('into_xml');

        $plannings = $this->planning_factory->getOrderedPlanningsWithBacklogTracker(
            $this->getCurrentUser(),
            $this->group_id
        );

        $this->agiledashboard_xml_exporter->export($this->request->getProject(), $root_node, $plannings);
    }

    /**
     * @throws Exception
     */
    public function importOnlyAgileDashboard()
    {
        $this->checkUserIsAdmin();
        $project = $this->request->getProject();
        $this->redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin($project);

        $xml      = $this->request->get('xml_content')->agiledashboard;
        $rng_path = realpath(__DIR__ . '/../../resources/xml_project_agiledashboard.rng');

        $this->xml_rng_validator->validate($xml, $rng_path);

        $this->milestones_in_sidebar_xml_import->import($xml, $project);

        $this->importPlannings($xml);

        $this->explicit_backlog_xml_import->importConfiguration($xml, $project);
    }

    /**
     * @throws Exception
     */
    public function importProject(
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
        \Psr\Log\LoggerInterface $logger,
    ): void {
        $this->checkUserIsAdmin();
        $project = $this->request->getProject();
        $this->redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin($project);

        $xml = $this->request->get('xml_content');
        if (! isset($xml->agiledashboard)) {
            return;
        }

        $rng_path = realpath(ForgeConfig::get('tuleap_dir') . '/src/common/xml/resources/project/project.rng');

        $partial_element = SimpleXMLElementBuilder::buildSimpleXMLElementToLoadHugeFiles((string) $xml->asXml());
        $this->external_field_extractor->extractExternalFieldFromProjectElement($partial_element);
        $this->xml_rng_validator->validate($partial_element, $rng_path);

        $xml_agiledashboard = $xml->agiledashboard;

        $this->milestones_in_sidebar_xml_import->import($xml_agiledashboard, $project);

        $this->importPlannings($xml_agiledashboard);

        $this->explicit_backlog_xml_import->importConfiguration($xml_agiledashboard, $project);
        $this->explicit_backlog_xml_import->importContent(
            $xml_agiledashboard,
            $project,
            $this->request->getCurrentUser(),
            $artifact_id_mapping,
            $logger
        );
    }

    /**
     * @throws Exception
     */
    private function importPlannings(SimpleXMLElement $xml): void
    {
        $data = $this->agiledashboard_xml_importer->toArray($xml, $this->request->get('mapping'));

        foreach ($data['plannings'] as $planning) {
            $request_params = [
                'planning'    => $planning,
                'group_id'    => $this->group_id,
                'planning_id' => '',
            ];

            $request = new Codendi_Request($request_params);

            if ($this->planning_request_validator->isValid($request)) {
                $this->planning_factory->createPlanning(
                    $this->group_id,
                    PlanningParameters::fromArray($planning)
                );
            } else {
                /**
                 * See https://github.com/vimeo/psalm/issues/4669
                 * @psalm-taint-escape html
                 */
                $planning_to_display_for_human = $planning;
                throw new Exception('Planning is not valid: ' . print_r($planning_to_display_for_human, true));
            }
        }
    }

    public function redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin(Project $project): void
    {
        $planning_administration_delegation = new PlanningAdministrationDelegation($project);
        $this->event_dispatcher->dispatch($planning_administration_delegation);

        if ($planning_administration_delegation->isPlanningAdministrationDelegated()) {
            $this->redirect(['group_id' => $project->getID(), 'action' => 'admin']);
        }
    }
}
