<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use CSRFSynchronizerToken;
use TemplateRendererFactory;
use Codendi_Request;
use Response;
use Feedback;
use ForgeConfig;
use ProjectManager;
use Tuleap\Admin\AdminPageRenderer;

class NatureConfigController {
    private static $TEMPLATE = 'siteadmin-config/natures';
    private static $URL      = '/plugins/tracker/config.php?action=natures';

    /** @var ProjectManager */
    private $project_manager;

    /** @var AllowedProjectsConfig */
    private $allowed_projects_config;

    /** @var NatureCreator */
    private $nature_creator;

    /** @var NaturePresenterFactory */
    private $nature_presenter_factory;

    /** @var NatureEditor */
    private $nature_editor;

    /** @var NatureDeletor */
    private $nature_deletor;

    /** @var AdminPageRenderer */
    private $admin_page_rendered;

    /** @var NatureUsagePresenterFactory */
    private $nature_usage_presenter_factory;

    public function __construct(
        ProjectManager $project_manager,
        AllowedProjectsConfig $allowed_projects_config,
        NatureCreator $nature_creator,
        NatureEditor $nature_editor,
        NatureDeletor $nature_deletor,
        NaturePresenterFactory $nature_presenter_factory,
        NatureUsagePresenterFactory $nature_usage_presenter_factory,
        AdminPageRenderer $admin_page_rendered
    ) {
        $this->project_manager                = $project_manager;
        $this->nature_creator                 = $nature_creator;
        $this->nature_presenter_factory       = $nature_presenter_factory;
        $this->nature_editor                  = $nature_editor;
        $this->nature_deletor                 = $nature_deletor;
        $this->allowed_projects_config        = $allowed_projects_config;
        $this->admin_page_rendered            = $admin_page_rendered;
        $this->nature_usage_presenter_factory = $nature_usage_presenter_factory;
    }

    public function index(CSRFSynchronizerToken $csrf, Response $response) {
        $title  = $GLOBALS['Language']->getText('plugin_tracker_config', 'title');

        $this->admin_page_rendered->renderANoFramedPresenter(
            $title,
            TRACKER_TEMPLATE_DIR,
            self::$TEMPLATE,
            $this->getNatureConfigPresenter($title, $csrf)
        );
    }

    public function createNature(Codendi_Request $request, Response $response) {
        try {
            $this->nature_creator->create(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );

            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_success',
                    $request->get('shortname')
                )
            );
        } catch (NatureManagementException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_error',
                    $exception->getMessage()
                )
            );
        }
        $response->redirect(self::$URL);
    }

    public function editNature(Codendi_Request $request, Response $response) {
        try {
            $this->nature_editor->edit(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );

            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'edit_success',
                    $request->get('shortname')
                )
            );
        } catch (NatureManagementException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'edit_error',
                    $exception->getMessage()
                )
            );
        }
        $response->redirect(self::$URL);
    }

    public function deleteNature(Codendi_Request $request, Response $response) {
        try {
            $this->nature_deletor->delete($request->get('shortname'));

            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_success')
            );

        } catch (NatureManagementException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_error', $exception->getMessage())
            );
        }
        $response->redirect(self::$URL);
    }

    public function allowProject(Codendi_Request $request, Response $response) {
        $project_to_allow = $request->get('project-to-allow');
        if (! empty($project_to_allow)) {
            $this->allowNatureUsageForProject($project_to_allow, $response);
        }

        $response->redirect(self::$URL);
    }

    public function revokeProject(Codendi_Request $request, Response $response) {
        $project_ids_to_remove = $request->get('project-ids-to-revoke');
        if (! empty($project_ids_to_remove)) {
            $this->revokeProjectsAuthorization($project_ids_to_remove, $response);
        }

        $response->redirect(self::$URL);
    }

    /** @return NatureConfigPresenter */
    private function getNatureConfigPresenter($title, CSRFSynchronizerToken $csrf) {
        $natures = $this->nature_presenter_factory->getAllNatures();

        $natures_usage = $this->nature_usage_presenter_factory->getNaturesUsagePresenters($natures);

        return new NatureConfigPresenter($title, $natures_usage, $csrf, $this->getAllowedProjects($csrf));
    }

    private function getAllowedProjects(CSRFSynchronizerToken $csrf) {
        $allowed_projects = $this->allowed_projects_config->getAllProjects();

        $presenter = new AllowedProjectsPresenter($csrf, $allowed_projects);

        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor'
        );

        return $renderer->renderToString($presenter::TEMPLATE, $presenter);
    }

    private function revokeProjectsAuthorization(array $project_ids_to_remove, Response $response) {
        if (count($project_ids_to_remove) > 0 &&
            $this->allowed_projects_config->removeProjectIds($project_ids_to_remove)
        ){
            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_revoke_project')
            );
        } else {
            $this->sendUpdateProjectListError($response);
        }
    }

    private function allowNatureUsageForProject($project_to_migrate, Response $response) {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_migrate);

        if (! $project || $project->isError()) {
            $this->sendUpdateProjectListError($response);
            return;
        }

        $this->allowed_projects_config->addProject($project);

        $response->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_allow_project')
        );
    }

    private function sendUpdateProjectListError(Response $response) {
        $response->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'allowed_project_update_project_list_error')
        );
    }
}
