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
    private $group_id;

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

        $this->group_id          = (int) $request->get('project')->getId();
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->plugin_theme_path = $plugin_theme_path;
    }

    public function index() {
        $agile_dashboard_node = $this->request->get('into_xml')->addChild('agiledashboard');

        $plannings = $this->planning_factory->getPlanningsShortAccess($this->getCurrentUser(), $this->group_id, $this->milestone_factory, $this->plugin_theme_path);

        $xml_exporter = new AgileDashboard_XMLExporter();
        $xml_exporter->export($agile_dashboard_node, $plannings);
    }

    public function create() {
        $this->checkUserIsAdmin();
        $validator = new Planning_RequestValidator($this->planning_factory);
        
        $xml = $this->request->get('into_xml')->agiledashboard;

        $xml_importer = new AgileDashboard_XMLImporter();
        $plannings = $xml_importer->toArray($xml);

        foreach ($plannings as $planning) {
            $request_params = array(
                'planning' => $planning,
                'group_id' => $this->group_id,
                'planning_id' => ''
            );

            $request = new Codendi_Request($request_params);

            if ($validator->isValid($request)) {
                $this->planning_factory->createPlanning($this->group_id,
                                                        PlanningParameters::fromArray($this->request->get('planning')));
            } else {
                // TODO: Error message should reflect validation detail
                $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));

            }
        }
    }

    private function checkUserIsAdmin() {
        $project = $this->request->getProject();
        $user    = $this->request->getCurrentUser();
        if (! $project->userIsAdmin($user)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
        //    $this->redirect(array('group_id' => $this->group_id));
            // the below is only run by tests (redirect should exit but is mocked)
            throw new Exception($GLOBALS['Language']->getText('global', 'perm_denied'));
        }
    }
}

?>
