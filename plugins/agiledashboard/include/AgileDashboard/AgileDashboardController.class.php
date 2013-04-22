<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 
require_once 'common/mvc2/PluginController.class.php';

/**
 * Handles the HTTP actions related to  the agile dashborad as a whole.
 * 
 */
class AgileDashboard_Controller extends MVC2_PluginController {

    /**
     *
     * @var int
     */
    protected $group_id;

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

    public function __construct(Codendi_Request $request, PlanningFactory $planning_factory, Planning_MilestoneFactory $milestone_factory, $plugin_theme_path) {
        parent::__construct('agiledashboard', $request);

        $this->group_id          = (int) $request->get('project_id');
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->plugin_theme_path = $plugin_theme_path;
    }

    public function index() {
        $root_node = $this->request->get('into_xml');

        $plannings = $this->planning_factory->getPlanningsWithBacklogTracker(
            $this->getCurrentUser(),
            $this->group_id,
            $this->planning_factory
        );

        $xml_exporter = new AgileDashboard_XMLExporter(new XmlValidator());
        $xml_exporter->export($root_node, $plannings);
    }

    public function createMultiplefromXml() {
        $this->checkUserIsAdmin();
        
        /* @var $xml SimpleXMLElement */
        $xml = $this->request->get('xml_content')->agiledashboard;

        $xml_importer = new AgileDashboard_XMLImporter();
        $data = $xml_importer->toArray($xml, $this->request->get('mapping'));

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

?>
