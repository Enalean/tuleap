<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * Handles the HTTP actions related to a planning.
 * 
 * TODO: Rename this file to PlanningController.class.php, to be consistent with
 * other classes. 
 */
class Planning_Controller extends MVC2_PluginController {

    const AGILE_DASHBOARD_TEMPLATE_NAME = 'agile_dashboard_template.xml';

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Planning_ShortAccessFactory */
    private $planning_shortaccess_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var String */
    private $plugin_theme_path;

    /** @var ProjectManager */
    private $project_manager;

    /** @var ProjectXMLExporter */
    private $xml_exporter;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        Planning_ShortAccessFactory $planning_shortaccess_factory,
        Planning_MilestoneFactory $milestone_factory,
        ProjectManager $project_manager,
        ProjectXMLExporter $xml_exporter,
        $plugin_theme_path
    ) {
        parent::__construct('agiledashboard', $request);
        
        $this->group_id                     = (int)$request->get('group_id');
        $this->planning_factory             = $planning_factory;
        $this->planning_shortaccess_factory = $planning_shortaccess_factory;
        $this->milestone_factory            = $milestone_factory;
        $this->project_manager              = $project_manager;
        $this->xml_exporter                 = $xml_exporter;
        $this->plugin_theme_path            = $plugin_theme_path;
    }
    
    public function admin() {
        return $this->renderToString(
            'admin',
            $this->getListPresenter(
                $this->getCurrentUser(),
                $this->group_id
            )
        );
    }

    private function getListPresenter(PFUser $user, $group_id) {
        $can_create_planning         = true;
        $tracker_uri                 = '';
        $root_planning_name          = '';
        $potential_planning_trackers = array();
        $root_planning               = $this->planning_factory->getRootPlanning($user, $group_id);
        if ($root_planning) {
            $can_create_planning         = count($this->planning_factory->getAvailablePlanningTrackers($user, $group_id)) > 0;
            $tracker_uri                 = $root_planning->getPlanningTracker()->getUri();
            $root_planning_name          = $root_planning->getName();
            $potential_planning_trackers = $this->planning_factory->getPotentialPlanningTrackers($user, $group_id);
        }

        return new Planning_ListPresenter(
            $this->getPlanningAdminPresenterList($user, $group_id, $root_planning_name),
            $group_id,
            $can_create_planning,
            $tracker_uri,
            $root_planning_name,
            $potential_planning_trackers
        );
    }

    private function getPlanningAdminPresenterList(PFUser $user, $group_id, $root_planning_name) {
        $plannings                 = array();
        $planning_out_of_hierarchy = array();
        foreach ($this->planning_factory->getPlanningsOutOfRootPlanningHierarchy($user, $group_id) as $planning) {
            $planning_out_of_hierarchy[$planning->getId()] = true;
        }
        foreach ($this->planning_factory->getPlannings($user, $group_id) as $planning) {
            if (isset($planning_out_of_hierarchy[$planning->getId()])) {
                $plannings[] = new Planning_PlanningOutOfHierarchyAdminPresenter($planning, $root_planning_name);
            } else {
                $plannings[] = new Planning_PlanningAdminPresenter($planning);
            }
        }
        return $plannings;
    }

    public function index() {
        try {
            $project_id = $this->request->getProject()->getID();
            $plannings = $this->getPlanningsShortAccess($this->group_id);
        } catch (Planning_InvalidConfigurationException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $plannings = array();
        }
        $presenter = new Planning_IndexPresenter(
            $plannings,
            $this->plugin_theme_path,
            $project_id,
            $this->isUserAdmin()
        );
        return $this->renderToString('index', $presenter);
    }

    /**
     * @return bool
     */
    private function isUserAdmin() {
        return $this->request->getProject()->userIsAdmin($this->request->getCurrentUser());
    }

    /**
     * Redirects a non-admin user to the agile dashboard home page
     */
    private function redirectNonAdmin() {
        if (! $this->isUserAdmin()) {
            $this->redirect(array('group_id'=>$this->group_id));
        }
    }
    
    public function new_() {
        $planning  = $this->planning_factory->buildNewPlanning($this->group_id);
        $presenter = $this->getFormPresenter($this->request->getCurrentUser(), $planning);

        return $this->renderToString('new', $presenter);
    }


    public function importForm() {
        $this->redirectNonAdmin();

        $template_file = new Valid_File('template_file');
        $template_file->required();

        if ($this->request->validFile($template_file)) {
            $this->importConfiguration();
        }

        $presenter = new Planning_ImportTemplateFormPresenter($this->group_id);
        return $this->renderToString('import', $presenter);
    }

    private function importConfiguration() {
        $xml_importer = new ProjectXMLImporter(EventManager::instance(), ProjectManager::instance());

        try {
            $xml_importer->import($this->group_id, $_FILES["template_file"]["tmp_name"]);
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_template_success') );
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_import') );
        }
    }

    /**
     * Exports the agile dashboard configuration as an XML file
     */
    public function exportToFile() {
        try {
            $project = $this->getProjectFromRequest();
            $xml = $this->getFullConfigurationAsXML($project);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_agiledashboard', 'export_failed'));
            $this->redirect(array('group_id'=>$this->group_id, 'action'=>'admin'));
        }

        $GLOBALS['Response']->sendXMLAttachementFile($xml, self::AGILE_DASHBOARD_TEMPLATE_NAME);
    }

    /**
     * @return Project
     * @throws Project_NotFoundException
     */
    private function getProjectFromRequest() {
        return $this->project_manager->getValidProject($this->group_id);
    }

    private function getFullConfigurationAsXML(Project $project) {
        return $this->xml_exporter->exportAsStandaloneXMLDocument($project);
    }

    public function create() {
        $this->checkUserIsAdmin();
        $validator = new Planning_RequestValidator($this->planning_factory);
        
        if ($validator->isValid($this->request)) {
            $this->planning_factory->createPlanning($this->group_id,
                                                    PlanningParameters::fromArray($this->request->get('planning')));
            
            $this->redirect(array('group_id' => $this->group_id, 'action' => 'admin'));
        } else {
            // TODO: Error message should reflect validation detail
            $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));
            $this->redirect(array('group_id' => $this->group_id, 'action' => 'new'));
        }
    }
    
    public function edit() {
        $planning  = $this->planning_factory->getPlanning($this->request->get('planning_id'));
        $presenter = $this->getFormPresenter($this->request->getCurrentUser(), $planning);
        
        return $this->renderToString('edit', $presenter);
    }
    
    private function getFormPresenter(PFUser $user, Planning $planning) {
        $group_id = $planning->getGroupId();

        $available_trackers          = $this->planning_factory->getAvailableBacklogTrackers($user, $group_id);
        $available_planning_trackers = $this->planning_factory->getAvailablePlanningTrackers($user, $group_id);
        $cardwall_admin              = $this->getCardwallConfiguration($planning);
        $available_planning_trackers[] = $planning->getPlanningTracker();

        return new Planning_FormPresenter($planning, $available_trackers, $available_planning_trackers, $cardwall_admin);
    }

    private function getCardwallConfiguration(Planning $planning) {
        $tracker  = $planning->getPlanningTracker();
        $view     = null;

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_PLANNING_CONFIG,
            array(
                'tracker' => $tracker,
                'view'    => &$view,
            )
        );

        return $view;
    }

    public function update() {
        $this->checkUserIsAdmin();
        $validator = new Planning_RequestValidator($this->planning_factory);
        
        if ($validator->isValid($this->request)) {
            $this->planning_factory->updatePlanning($this->request->get('planning_id'),
                                                    PlanningParameters::fromArray($this->request->get('planning')));
        } else {
            $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));
        }

        $this->updateCardwallConfig();

        $this->redirect(array('group_id'    => $this->group_id,
                              'planning_id' => $this->request->get('planning_id'),
                              'action'      => 'edit'));
    }

    private function updateCardwallConfig() {
        $tracker = $this->getPlanning()->getPlanningTracker();

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE,
            array(
                'request' => $this->request,
                'tracker' => $tracker,
            )
        );
    }

    public function delete() {
        $this->checkUserIsAdmin();
        $this->planning_factory->deletePlanning($this->request->get('planning_id'));
        return $this->redirect(array('group_id' => $this->group_id, 'action' => 'admin'));
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        return new BreadCrumb_AgileDashboard();
    }

    public function generateSystrayData() {
        $user  = $this->request->get('user');
        $links = $this->request->get('links');
        
        foreach ($user->getGroups() as $project) {
            if (! $project->usesService('plugin_agiledashboard')) {
                continue;
            }

            try {
                $plannings = $this->getPlanningsShortAccess($project->getID());

                /* @var $links Systray_LinksCollection */
                $links->append(
                    new Systray_AgileDashboardLink($project, $plannings)
                );
            } catch (Planning_InvalidConfigurationException $e) {
                //do nothing if the project configuration is invalid
            }
        }
    }

    public function getMoreMilestones() {
        $offset = $this->request->get('offset', 'uint', 0);
        $planning = $this->planning_factory->getPlanning($this->request->get('planning_id'));
        $short_access = $this->planning_shortaccess_factory->getShortAccessForPlanning(
            $planning,
            $this->getCurrentUser(),
            $this->milestone_factory,
            $this->plugin_theme_path,
            $offset
        );

        $this->render('shortaccess-milestones', $short_access);
    }

    /**
     *
     * @param int $projectId
     * @return Planning_ShortAccess[]
     */
    private function getPlanningsShortAccess($projectId) {
        return $this->planning_shortaccess_factory->getPlanningsShortAccess(
            $this->getCurrentUser(),
            $projectId,
            $this->milestone_factory,
            $this->plugin_theme_path
        );
    }
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }
}

?>
