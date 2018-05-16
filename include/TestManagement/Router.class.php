<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use BackendLogger;
use EventManager;
use PFUser;
use Plugin;
use Codendi_Request;
use Project;
use ProjectManager;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\TestManagement\Administration\StepFieldUsageDetector;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use UserManager;
use XMLImportHelper;

class Router {

    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Config
     */
    private $config;

    /** @var TrackerFactory */
    private $tracker_factory;

    /**
     * @param \Service
     */
    private $service;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var ProjectManager
     */
    private $user_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $artifact_links_usage_updater;

    /**
     * @var StepFieldUsageDetector
     */
    private $step_field_usage_detector;

    public function __construct(
        Plugin $plugin,
        Config $config,
        TrackerFactory $tracker_factory,
        ProjectManager $project_manager,
        UserManager $user_manager,
        EventManager $event_manager,
        ArtifactLinksUsageUpdater $artifact_links_usage_updater,
        StepFieldUsageDetector $step_field_usage_detector
    ) {
        $this->config                       = $config;
        $this->plugin                       = $plugin;
        $this->tracker_factory              = $tracker_factory;
        $this->project_manager              = $project_manager;
        $this->user_manager                 = $user_manager;
        $this->event_manager                = $event_manager;
        $this->artifact_links_usage_updater = $artifact_links_usage_updater;
        $this->step_field_usage_detector    = $step_field_usage_detector;
    }

    public function route(Codendi_Request $request)
    {
        $csrf_token = new \CSRFSynchronizerToken(
            TESTMANAGEMENT_BASE_URL .'/?'. http_build_query(['group_id' => $request->get('group_id')])
        );
        switch ($request->get('action')) {
            case 'admin':
                $this->checkUserCanAdministrate($request->getProject(), $this->user_manager->getCurrentUser());
                $controller = new AdminController(
                    $request,
                    $this->config,
                    $this->tracker_factory,
                    $this->event_manager,
                    $csrf_token,
                    $this->step_field_usage_detector
                );
                $this->renderAction($controller, 'admin', $request);
                break;
            case 'admin-update':
                $this->checkUserCanAdministrate($request->getProject(), $this->user_manager->getCurrentUser());
                $controller = new AdminController(
                    $request,
                    $this->config,
                    $this->tracker_factory,
                    $this->event_manager,
                    $csrf_token,
                    $this->step_field_usage_detector
                );
                $this->executeAction($controller, 'update');
                $this->renderIndex($request);
                break;
            case 'create-config':
                $this->checkUserCanAdministrate($request->getProject(), $this->user_manager->getCurrentUser());
                $controller = new StartTestManagementController(
                    $this->tracker_factory,
                    new BackendLogger(),
                    TrackerXmlImport::build(
                        new XMLImportHelper(UserManager::instance())
                    ),
                    $this->artifact_links_usage_updater,
                    $csrf_token
                );
                $this->executeAction($controller, 'createConfig', array($request));
                $this->renderIndex($request);
                break;
            default:
                if ($this->config->isConfigNeeded($request->getProject())) {
                    $controller = new StartTestManagementController(
                        $this->tracker_factory,
                        new BackendLogger(),
                        TrackerXmlImport::build(
                            new XMLImportHelper(UserManager::instance())
                        ),
                        $this->artifact_links_usage_updater,
                        $csrf_token
                    );

                    $this->renderAction(
                        $controller,
                        'misconfiguration',
                        $request,
                        array($request)
                    );
                    return;
                }

                $this->renderIndex($request);
        }
    }

    public function renderIndex(Codendi_Request $request) {
        $controller = new IndexController($request, $this->config, $this->tracker_factory, $this->event_manager);
        $this->renderAction($controller, 'index', $request);
    }

    /**
     * Renders the given controller action, with page header/footer.
     *
     * @param mixed           $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param Codendi_Request $request     The request
     * @param array           $args        Arguments to pass to the controller action method.
     */
    private function renderAction(
        $controller,
        $action_name,
        Codendi_Request $request,
        array $args = array()
    ) {
        $content = $this->executeAction($controller, $action_name, $args);

        $this->displayHeader($controller, $request, $this->getHeaderTitle($action_name));
        echo $content;
        $this->displayFooter($request);
    }

    /**
     * Executes the given controller action, without rendering page header/footer.
     * Useful for actions ending with a redirection instead of page rendering.
     *
     * @param mixed           $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param array           $args        Arguments to pass to the controller action method.
     */
    private function executeAction(
        $controller,
        $action_name,
        array $args = array()
    ) {
        return call_user_func_array(array($controller, $action_name), $args);
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
            'index' => $GLOBALS['Language']->getText('plugin_testmanagement', 'service_lbl_key'),
            'admin' => $GLOBALS['Language']->getText('global', 'Admin'),
            'misconfiguration' => dgettext(
                'tuleap-testmanagement',
                'Configuration incomplete'
            )
        );

        return $header_title[$action_name];
    }

    /**
     * Retrieves the Agile Dashboard Service instance matching the request group id.
     *
     * @param Codendi_Request $request
     *
     * @return \Service
     */
    private function getService(Codendi_Request $request) {
        if ($this->service == null) {
            $project = $request->getProject();
            $this->service = $project->getService('plugin_testmanagement');
        }
        return $this->service;
    }

    /**
     * Renders the top banner + navigation for all pages.
     *
     * @param mixed           $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     */
    private function displayHeader(
        $controller,
        Codendi_Request $request,
        $title
    ) {
        $service = $this->getService($request);
        if (! $service) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    $GLOBALS['Language']->getText('plugin_testmanagement', 'service_lbl_key'))
            );
        }

        $project     = $request->getProject();
        $toolbar     = array();
        $breadcrumbs = $controller->getBreadcrumbs();
        if ($this->userIsAdmin($request)) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Admin'),
                'url'   => TESTMANAGEMENT_BASE_URL .'/?'. http_build_query(array(
                    'group_id' => $request->get('group_id'),
                    'action'   => 'admin',
                ))
            );
        }

        $service->displayHeader($title, $breadcrumbs->getCrumbs($project), $toolbar, array('body_class' => array('testmanagement')));
    }

    private function userIsAdmin(Codendi_Request $request) {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    /**
     * Renders the bottom footer for all Agile Dashboard pages.
     *
     * @param Codendi_Request $request
     */
    private function displayFooter(Codendi_Request $request) {
        $this->getService($request)->displayFooter();
    }

   protected function checkUserCanAdministrate(Project $project, PFUser $user)
    {
        if (! $user->isAdmin($project->getId())) {
            throw new UserIsNotAdministratorException();
        }
    }

}
