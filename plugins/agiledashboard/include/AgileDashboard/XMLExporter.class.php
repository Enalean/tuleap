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

use Tuleap\AgileDashboard\ExplicitBacklog\XMLExporter as ExplicitBacklogXMLExporter;
use Tuleap\AgileDashboard\Planning\XML\XMLExporter as PlanningXMLExporter;

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

    public function __construct(
        XML_RNGValidator $xml_validator,
        ExplicitBacklogXMLExporter $explicit_backlog_xml_exporter,
        PlanningXMLExporter $planning_xml_exporter
    ) {
        $this->xml_validator                 = $xml_validator;
        $this->explicit_backlog_xml_exporter = $explicit_backlog_xml_exporter;
        $this->planning_xml_exporter = $planning_xml_exporter;
    }

    /**
     * @throws AgileDashboard_XMLExporterUnableToGetValueException
     */
    public function export(Project $project, SimpleXMLElement $xml_element, array $plannings)
    {
        $agiledashboard_node = $xml_element->addChild(self::NODE_AGILEDASHBOARD);

        $this->explicit_backlog_xml_exporter->exportExplicitBacklogConfiguration($project, $agiledashboard_node);
        $this->planning_xml_exporter->exportPlannings($agiledashboard_node, $plannings);

        $rng_path = realpath(AGILEDASHBOARD_BASE_DIR.'/../www/resources/xml_project_agiledashboard.rng');
        $this->xml_validator->validate($agiledashboard_node, $rng_path);
    }
}
