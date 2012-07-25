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

require_once 'common/plugin/Plugin.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/TrackerFactory.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/FormElement/Tracker_FormElementFactory.class.php';
require_once dirname(__FILE__) .'/BreadCrumbs/BreadCrumbGenerator.class.php';
require_once 'Planning/PlanningController.class.php';
require_once 'Planning/MilestoneController.class.php';
require_once 'Planning/PlanningFactory.class.php';
require_once 'Planning/ArtifactCreationController.class.php';
require_once 'Planning/MilestoneSelectorController.class.php';
require_once 'Planning/ViewBuilder.class.php';

/**
 * Routes HTTP (and maybe SOAP ?) requests to the appropriate controllers
 * (e.g. PlanningController, MilestoneController...).
 * 
 * See AgileDashboardRouter::route()
 * 
 * TODO: Layout management should be extracted and moved to controllers or views.
 */
class AgileDashboardRouter {
    
    /**
     * @var Plugin
     */
    private $plugin;
    
    /**
     * @param Service
     */
    private $service;
    
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Routes the given request to the appropriate controller.
     * 
     * TODO:
     *   - Use a 'resource' parameter to deduce the controller (e.g. someurl/?resource=planning&id=2 )
     *   - Pass $request to action methods
     * 
     * @param Codendi_Request $request 
     */
    public function route(Codendi_Request $request) {
        $controller = $this->buildController($request);
        
        switch($request->get('action')) {
            case 'show':
                $this->routeShowPlanning($request);
                break;
            case 'new':
                $this->renderAction($controller, 'new_', $request);
                break;
            case 'create':
                $this->executeAction($controller, 'create');
                break;
            case 'edit':
                $this->renderAction($controller, 'edit', $request);
                break;
            case 'update':
                $this->executeAction($controller, 'update');
                break;
            case 'delete':
                $this->executeAction($controller, 'delete');
                break;
            case 'index':
            default:
                $this->renderAction($controller, 'index', $request);
        }
    }
    
    /**
     * Returns the page title according to the current controller action name.
     * 
     * TODO:
     *   - Use a layout template, and move title retrieval to the appropriate presenters.
     * 
     * @param string $action_name The controller action name (e.g. index, show...).
     * 
     * @return string
     */
    private function getHeaderTitle($action_name) {
        $header_title = array(
            'index' => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_index'),
            'new_'  => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_new'),
            'edit'  => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_edit'),
            'show'  => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_show')
        );
        
        return $header_title[$action_name];
    }
    
    /**
     * Retrieves the Agile Dashboard Service instance matching the request group id.
     * 
     * @param Codendi_Request $request
     * 
     * @return Service 
     */
    private function getService(Codendi_Request $request) {
        if ($this->service == null) {
            $project = ProjectManager::instance()->getProject($request->get('group_id'));
            $this->service = $project->getService('plugin_agiledashboard');
        }
        return $this->service;
    }
    
    /**
     * Renders the top banner + navigation for all Agile Dashboard pages.
     * 
     * @param MVC2_Controller $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     */
    private function displayHeader(MVC2_Controller $controller,
                                   Codendi_Request $request,
                                                   $title) {
        
        $breadcrumbs = $controller->getBreadcrumbs($this->plugin->getPluginPath());
        $this->getService($request)->displayHeader($title, $breadcrumbs->getCrumbs(), array());
    }
    
    /**
     * Renders the bottom footer for all Agile Dashboard pages.
     * 
     * @param Codendi_Request $request 
     */
    private function displayFooter(Codendi_Request $request) {
        $this->getService($request)->displayFooter();
    }
    
    /**
     * Builds a new Planning_Controller instance.
     * 
     * @param Codendi_Request $request
     * 
     * @return Planning_Controller 
     */
    protected function buildController(Codendi_Request $request) {
        $planning_factory = new PlanningFactory(new PlanningDao(),
                                                TrackerFactory::instance());
        
        return new Planning_Controller($request, $planning_factory);
    }

    /**
     * Builds a new Milestone_Controller instance.
     * 
     * @param Codendi_Request $request
     * 
     * @return Planning_MilestoneController 
     */
    protected function buildMilestoneController(Codendi_Request $request) {
        return new Planning_MilestoneController(
            $request,
            $this->getMilestoneFactory(),
            $this->getProjectManager()
        );
    }
    
    /**
     * Renders the given controller action, with page header/footer.
     * 
     * @param MVC2_Controller $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param Codendi_Request $request     The request
     * @param array           $args        Arguments to pass to the controller action method.
     */
    protected function renderAction(MVC2_Controller $controller,
                                                    $action_name,
                                    Codendi_Request $request,
                                    array           $args = array()) {
        
        $this->displayHeader($controller, $request, $this->getHeaderTitle($action_name));
        $this->executeAction($controller, $action_name, $args);
        $this->displayFooter($request);
    }
    
    /**
     * Executes the given controller action, without rendering page header/footer.
     * Useful for actions ending with a redirection instead of page rendering.
     * 
     * @param MVC2_Controller $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param array           $args        Arguments to pass to the controller action method.
     */
    protected function executeAction(MVC2_Controller $controller,
                                                     $action_name,
                                     array           $args = array()) {
        
        call_user_func_array(array($controller, $action_name), $args);
    }

    /**
     * Builds a new cross-tracker search view builder.
     * 
     * TODO:
     *   - move to controller (this has nothing to do with routing)
     * 
     * @param Codendi_Request $request
     * 
     * @return Tracker_CrossSearch_ViewBuilder
     */
    protected function getViewBuilder(Codendi_Request $request) {
        
        $form_element_factory = Tracker_FormElementFactory::instance();
        $group_id             = $request->get('group_id');
        $user                 = $request->getCurrentUser();
        $object_god           = new TrackerManager();
        $planning_trackers    = $this->getPlanningFactory()->getPlanningTrackers($group_id, $user);
        $art_link_field_ids   = $form_element_factory->getArtifactLinkFieldsOfTrackers($planning_trackers);
        
        return new Planning_ViewBuilder(
            $form_element_factory, 
            $object_god->getCrossSearch($art_link_field_ids), 
            $object_god->getCriteriaBuilder($user, $planning_trackers)
        );
    }
    
    /**
     * Routes some milestone-related requests.
     * 
     * TODO:
     *   - merge into AgileDashboardRouter::route()
     * 
     * @param Codendi_Request $request 
     */
    public function routeShowPlanning(Codendi_Request $request) {
        $aid = $request->getValidated('aid', 'int', 0);
        switch ($aid) {
            case -1:
                $controller = new Planning_ArtifactCreationController($this->getPlanningFactory(), $request);
                $this->executeAction($controller, 'createArtifact');
                break;
            case 0:
                $controller = new Planning_MilestoneSelectorController($request, $this->getMilestoneFactory());
                $this->executeAction($controller, 'show');
            default:
                $controller = $this->buildMilestoneController($request);
                $action_arguments = array($this->getViewBuilder($request));
                $this->renderAction($controller, 'show', $request, $action_arguments);
        }
    }

    public function getProjectManager() {
        return ProjectManager::instance();
    }
    /**
     * Builds a new PlanningFactory instance.
     * 
     * TODO:
     *   - Use Planning_MilestoneFactory instead.
     * 
     * @return PlanningFactory 
     */
    protected function getPlanningFactory() {
        return new PlanningFactory(new PlanningDao(),
                                   TrackerFactory::instance());

    }

    /**
     * Builds a new Planning_MilestoneFactory instance.
     * @return Planning_MilestoneFactory 
     */
    protected function getMilestoneFactory() {
        return new Planning_MilestoneFactory(
            $this->getPlanningFactory(),
            Tracker_ArtifactFactory::instance(),
            Tracker_FormElementFactory::instance());
    }
}

?>
