<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use PFUser;
use Project;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\NotFoundException;
use Tuleap\TestManagement\Administration\AdminController;
use Tuleap\TestManagement\Administration\AdminTrackersRetriever;
use Tuleap\TestManagement\Administration\FieldUsageDetector;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use UserManager;
use Valid_UInt;
use XMLImportHelper;

class Router
{
    /**
     * @var Config
     */
    private $config;

    /** @var TrackerFactory */
    private $tracker_factory;

    /**
     * @var \Service|null
     */
    private $service;

    /**
     * @var UserManager
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
     * @var FieldUsageDetector
     */
    private $field_usage_detector;
    /**
     * @var TrackerChecker
     */
    private $tracker_checker;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var Valid_UInt
     */
    private $int_validator;
    /**
     * @var ProjectFlagsBuilder
     */
    private $project_flags_builder;
    /**
     * @var AdminTrackersRetriever
     */
    private $tracker_retriever;

    public function __construct(
        Config $config,
        TrackerFactory $tracker_factory,
        UserManager $user_manager,
        EventManager $event_manager,
        ArtifactLinksUsageUpdater $artifact_links_usage_updater,
        FieldUsageDetector $field_usage_detector,
        TrackerChecker $tracker_checker,
        VisitRecorder $visit_recorder,
        Valid_UInt $int_validator,
        ProjectFlagsBuilder $project_flags_builder,
        AdminTrackersRetriever $tracker_retriever,
    ) {
        $this->config                       = $config;
        $this->tracker_factory              = $tracker_factory;
        $this->user_manager                 = $user_manager;
        $this->event_manager                = $event_manager;
        $this->artifact_links_usage_updater = $artifact_links_usage_updater;
        $this->field_usage_detector         = $field_usage_detector;
        $this->tracker_checker              = $tracker_checker;
        $this->visit_recorder               = $visit_recorder;
        $this->int_validator                = $int_validator;
        $this->project_flags_builder        = $project_flags_builder;
        $this->tracker_retriever            = $tracker_retriever;
    }

    public function route(Codendi_Request $request): void
    {
        $csrf_token = new \CSRFSynchronizerToken(
            TESTMANAGEMENT_BASE_URL . '/?' . http_build_query(['group_id' => $request->get('group_id')])
        );
        switch ($request->get('action')) {
            case 'admin':
                $this->checkUserCanAdministrate($request->getProject(), $this->user_manager->getCurrentUser());
                $controller = new AdminController(
                    $request,
                    $this->config,
                    $this->event_manager,
                    $csrf_token,
                    $this->field_usage_detector,
                    $this->tracker_checker,
                    $this->int_validator,
                    $this->tracker_retriever
                );
                $this->renderAction($controller, 'admin', $request, false);
                break;
            case 'admin-update':
                $this->checkUserCanAdministrate($request->getProject(), $this->user_manager->getCurrentUser());
                $controller = new AdminController(
                    $request,
                    $this->config,
                    $this->event_manager,
                    $csrf_token,
                    $this->field_usage_detector,
                    $this->tracker_checker,
                    $this->int_validator,
                    $this->tracker_retriever
                );
                $this->executeAction($controller, 'update');
                if ($this->config->isConfigNeeded($request->getProject())) {
                    $this->renderStartTestManagement($request, $csrf_token);
                } else {
                    $this->renderIndex($request);
                }
                break;
            case 'create-config':
                $this->checkUserCanAdministrate($request->getProject(), $this->user_manager->getCurrentUser());
                $controller = $this->getTestManagementController($csrf_token);
                $this->executeAction($controller, 'createConfig', [$request]);
                $this->renderIndex($request);
                break;
            default:
                if ($this->config->isConfigNeeded($request->getProject())) {
                    $this->renderStartTestManagement($request, $csrf_token);
                    return;
                }

                $this->renderIndex($request);
        }
    }

    private function renderStartTestManagement(Codendi_Request $request, CSRFSynchronizerToken $csrf_token): void
    {
        $controller = $this->getTestManagementController($csrf_token);

        $this->renderAction(
            $controller,
            'misconfiguration',
            $request,
            false,
            [$request]
        );
    }

    public function renderIndex(Codendi_Request $request): void
    {
        $controller = new IndexController(
            $request,
            $this->config,
            $this->event_manager,
            $this->tracker_factory,
            $this->visit_recorder,
            $this->project_flags_builder,
            (new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao()))
        );
        $this->renderAction($controller, 'index', $request, true, [], ['reduce-help-button']);
    }

    /**
     * Renders the given controller action, with page header/footer.
     *
     * @param mixed           $controller  The controller instance.
     * @param string          $action_name The controller action name (e.g. index, show...).
     * @param Codendi_Request $request     The request
     * @param array           $args        Arguments to pass to the controller action method.
     * @param array           $extra_classes      Extra body classes
     *
     */
    private function renderAction(
        $controller,
        $action_name,
        Codendi_Request $request,
        bool $without_project_in_breadcrumb,
        array $args = [],
        array $extra_classes = [],
    ): void {
        $content = $this->executeAction($controller, $action_name, $args);

        $this->displayHeader($controller, $request, $this->getHeaderTitle($action_name), $without_project_in_breadcrumb, $extra_classes);
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
     *
     * @return mixed
     */
    private function executeAction(
        $controller,
        $action_name,
        array $args = [],
    ) {
        return call_user_func_array([$controller, $action_name], $args);
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
    private function getHeaderTitle($action_name)
    {
        $header_title = [
            'index' => dgettext('tuleap-testmanagement', 'Test Management'),
            'admin' => $GLOBALS['Language']->getText('global', 'Admin'),
            'misconfiguration' => dgettext(
                'tuleap-testmanagement',
                'Configuration incomplete'
            ),
        ];

        return $header_title[$action_name];
    }

    private function getService(Codendi_Request $request): \Service
    {
        if ($this->service === null) {
            $project       = $request->getProject();
            $this->service = $project->getService('plugin_testmanagement');
        }
        if ($this->service === null) {
            throw new NotFoundException(dgettext('tuleap-testmanagement', "Test Management service is not active in this project"));
        }

        return $this->service;
    }

    /**
     * Renders the top banner + navigation for all pages.
     *
     * @param mixed           $controller The controller instance
     * @param Codendi_Request $request    The request
     * @param string          $title      The page title
     * @param array           $extra_classes      Extra body classes
     */
    private function displayHeader(
        $controller,
        Codendi_Request $request,
        $title,
        bool $without_project_in_breadcrumb,
        array $extra_classes = [],
    ): void {
        $service = $this->getService($request);

        $project     = $request->getProject();
        $toolbar     = [];
        $breadcrumbs = $controller->getBreadcrumbs();

        $params_builder = HeaderConfigurationBuilder::get($title)
            ->withBodyClass(array_merge(['testmanagement'], $extra_classes));

        $params = $without_project_in_breadcrumb
            ? $params_builder->inProjectNotInBreadcrumbs($project, \testmanagementPlugin::SERVICE_SHORTNAME)->build()
            : $params_builder->inProject($project, \testmanagementPlugin::SERVICE_SHORTNAME)->build();

        $service->displayHeader(
            $title,
            $breadcrumbs->getCrumbs($project),
            $toolbar,
            $params
        );
    }

    /**
     * Renders the bottom footer for all Agile Dashboard pages.
     *
     */
    private function displayFooter(Codendi_Request $request): void
    {
        $this->getService($request)->displayFooter();
    }

    protected function checkUserCanAdministrate(Project $project, PFUser $user): void
    {
        if (! $user->isAdmin($project->getId())) {
            throw new UserIsNotAdministratorException();
        }
    }

    private function getTestManagementController(CSRFSynchronizerToken $csrf_token): StartTestManagementController
    {
        $tracker_xml_import = TrackerXmlImport::build(
            new XMLImportHelper(UserManager::instance())
        );

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        return new StartTestManagementController(
            $this->artifact_links_usage_updater,
            $csrf_token,
            $transaction_executor,
            new FirstConfigCreator(
                $this->config,
                $this->tracker_factory,
                $this->tracker_checker,
                new TestmanagementTrackersConfigurator(new TestmanagementTrackersConfiguration()),
                new TestmanagementTrackersCreator($tracker_xml_import, BackendLogger::getDefaultLogger())
            )
        );
    }
}
