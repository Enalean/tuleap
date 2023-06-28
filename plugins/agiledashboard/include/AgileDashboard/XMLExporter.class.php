<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter as ExplicitBacklogXMLExporter;
use Tuleap\Kanban\SemanticStatusNotFoundException;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\XML\KanbanXMLExporter;
use Tuleap\AgileDashboard\Planning\XML\XMLExporter as PlanningXMLExporter;
use Tuleap\Kanban\KanbanDao;

class AgileDashboard_XMLExporter
{
    /**  @var XML_RNGValidator */
    private $xml_validator;

    public const NODE_AGILEDASHBOARD = 'agiledashboard';

    /**
     * @var ExplicitBacklogXMLExporter
     */
    private $explicit_backlog_xml_exporter;

    /**
     * @var PlanningXMLExporter
     */
    private $planning_xml_exporter;
    /**
     * @var KanbanXMLExporter
     */
    private $kanban_XML_exporter;

    public function __construct(
        XML_RNGValidator $xml_validator,
        PlanningXMLExporter $planning_xml_exporter,
        KanbanXMLExporter $kanban_XML_exporter,
        ExplicitBacklogXMLExporter $explicit_backlog_xml_exporter,
    ) {
        $this->xml_validator                 = $xml_validator;
        $this->kanban_XML_exporter           = $kanban_XML_exporter;
        $this->explicit_backlog_xml_exporter = $explicit_backlog_xml_exporter;
        $this->planning_xml_exporter         = $planning_xml_exporter;
    }

    public static function build(): AgileDashboard_XMLExporter
    {
        $tracker_factory = TrackerFactory::instance();

        return new AgileDashboard_XMLExporter(
            new XML_RNGValidator(),
            new PlanningXMLExporter(new PlanningPermissionsManager()),
            new KanbanXMLExporter(
                new AgileDashboard_ConfigurationDao(),
                new KanbanFactory(
                    $tracker_factory,
                    new KanbanDao()
                )
            ),
            new ExplicitBacklogXMLExporter(
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao()
            )
        );
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     * @throws SemanticStatusNotFoundException
     * @throws XML_ParseException
     */
    public function export(Project $project, SimpleXMLElement $xml_element, array $plannings): void
    {
        $agiledashboard_node = $xml_element->addChild(self::NODE_AGILEDASHBOARD);

        $this->explicit_backlog_xml_exporter->exportExplicitBacklogConfiguration($project, $agiledashboard_node);
        $this->planning_xml_exporter->exportPlannings($agiledashboard_node, $plannings);

        $this->kanban_XML_exporter->export($agiledashboard_node, $project);

        $this->validateXML($agiledashboard_node);
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     * @throws SemanticStatusNotFoundException
     * @throws XML_ParseException
     */
    public function exportFull(Project $project, SimpleXMLElement $xml_element, array $plannings)
    {
        $agiledashboard_node = $xml_element->addChild(self::NODE_AGILEDASHBOARD);

        $this->explicit_backlog_xml_exporter->exportExplicitBacklogConfiguration($project, $agiledashboard_node);
        $this->explicit_backlog_xml_exporter->exportExplicitBacklogContent($project, $agiledashboard_node);
        $this->planning_xml_exporter->exportPlannings($agiledashboard_node, $plannings);

        $this->kanban_XML_exporter->export($agiledashboard_node, $project);

        $this->validateXML($agiledashboard_node);
    }

    /**
     * @throws XML_ParseException
     */
    private function validateXML(SimpleXMLElement $agiledashboard_node): void
    {
        $rng_path = realpath(__DIR__ . '/../../resources/xml_project_agiledashboard.rng');
        $this->xml_validator->validate($agiledashboard_node, $rng_path);
    }
}
