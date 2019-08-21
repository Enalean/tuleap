<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

/**
 * Handles the HTTP actions related to  the agile dashborad as a whole.
 *
 */
class AgileDashboard_XMLController extends MVC2_PluginController
{
    /**
     *
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     *
     * @var string
     */
    private $plugin_theme_path;


    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        Planning_MilestoneFactory $milestone_factory,
        $plugin_theme_path
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id          = $request->getValidated('project_id', 'uint');
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->plugin_theme_path = $plugin_theme_path;
    }

    public function export()
    {
        $root_node = $this->request->get('into_xml');

        $plannings = $this->planning_factory->getOrderedPlanningsWithBacklogTracker(
            $this->getCurrentUser(),
            $this->group_id
        );

        $xml_exporter = new AgileDashboard_XMLExporter(new XML_RNGValidator(), new PlanningPermissionsManager());
        $xml_exporter->export($root_node, $plannings);
    }

    public function importOnlyAgileDashboard()
    {
        $this->checkUserIsAdmin();

        $xml           = $this->request->get('xml_content')->agiledashboard;
        $xml_validator = new XML_RNGValidator();
        $rng_path      = realpath(AGILEDASHBOARD_BASE_DIR . '/../www/resources/xml_project_agiledashboard.rng');

        $xml_validator->validate($xml, $rng_path);

        $this->import($xml);
    }

    public function importProject()
    {
        $this->checkUserIsAdmin();

        $xml           = $this->request->get('xml_content');
        $xml_validator = new XML_RNGValidator();
        $rng_path      = realpath(ForgeConfig::get('tuleap_dir') . '/src/common/xml/resources/project/project.rng');

        $xml_validator->validate($xml, $rng_path);
        $xml = $this->request->get('xml_content')->agiledashboard;

        $this->import($xml);
    }

    private function import(SimpleXMLElement $xml)
    {
        $xml_importer = new AgileDashboard_XMLImporter();
        $data         = $xml_importer->toArray($xml, $this->request->get('mapping'));

        $validator = new Planning_RequestValidator($this->planning_factory);

        foreach ($data['plannings'] as $planning) {
            $request_params = array(
                'planning'      => $planning,
                'group_id'      => $this->group_id,
                'planning_id'   => ''
            );

            $request = new Codendi_Request($request_params);

            if ($validator->isValid($request)) {
                $this->planning_factory->createPlanning(
                    $this->group_id,
                    PlanningParameters::fromArray($planning)
                );
            } else {
                throw new Exception('Planning is not valid: '.  print_r($planning, true));
            }
        }
    }
}
