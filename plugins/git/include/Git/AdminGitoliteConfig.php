<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Layout\JavascriptAssetGeneric;

class Git_AdminGitoliteConfig //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string ACTION_UPDATE_CONFIG                      = 'update_config';
    public const string ACTION_UPDATE_BIG_OBJECT_ALLOWED_PROJECTS = 'update-big-objects-allowed-projects';

    /**
     * @var Git_SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /** @var AdminPageRenderer */
    private $admin_page_renderer;

    /**
     * @var BigObjectAuthorizationManager
     */
    private $big_object_authorization_manager;

    private JavascriptAssetGeneric $asset;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        ProjectManager $project_manager,
        Git_SystemEventManager $system_event_manager,
        AdminPageRenderer $admin_page_renderer,
        BigObjectAuthorizationManager $big_object_authorization_manager,
        JavascriptAssetGeneric $asset,
    ) {
        $this->csrf                             = $csrf;
        $this->project_manager                  = $project_manager;
        $this->system_event_manager             = $system_event_manager;
        $this->admin_page_renderer              = $admin_page_renderer;
        $this->big_object_authorization_manager = $big_object_authorization_manager;
        $this->asset                            = $asset;
    }

    public function process(Codendi_Request $request)
    {
        $action = $request->get('action');

        if ($action === false) {
            return;
        }

        switch ($action) {
            case self::ACTION_UPDATE_CONFIG:
                $this->csrf->check();
                $this->regenerateGitoliteConfigForAProject($request);
                break;
            case self::ACTION_UPDATE_BIG_OBJECT_ALLOWED_PROJECTS:
                $this->csrf->check();
                $this->updateBigObjectAllowedProjects($request);
                break;
            default:
                $GLOBALS['Response']->addFeedback(
                    'error',
                    dgettext('tuleap-git', 'Bad request.')
                );
        }

        return true;
    }

    private function regenerateGitoliteConfigForAProject(Codendi_Request $request)
    {
        $project = $this->getProject($request->get('gitolite_config_project'));

        if (! $project) {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-git', 'Project does not exist.')
            );
            return;
        }

        $this->system_event_manager->queueRegenerateGitoliteConfig($project->getID());

        $GLOBALS['Response']->addFeedback(
            'info',
            sprintf(dgettext('tuleap-git', 'Regenerating Gitolite config for project %1$s. Please wait few minutes.'), $project->getPublicName())
        );
    }

    /**
     * @return Project
     */
    private function getProject($project_name_from_autocomplete)
    {
        return $this->project_manager->getProjectFromAutocompleter($project_name_from_autocomplete);
    }

    private function updateBigObjectAllowedProjects(Codendi_Request $request)
    {
        if ($request->get('revoke-project')) {
            $this->revokeProjects($request);
        }

        if ($request->get('allow-project')) {
            $this->allowProject($request);
        }
    }

    private function revokeProjects(Codendi_Request $request)
    {
        $project_ids = $request->get('project-ids-to-revoke');

        if (empty($project_ids)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'No project selected')
            );

            return;
        }

        $this->big_object_authorization_manager->revokeProjectAuthorization($project_ids);
        $this->system_event_manager->queueProjectsConfigurationUpdate($project_ids);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-git', 'Project(s) successfully removed')
        );
    }

    private function allowProject(Codendi_Request $request)
    {
        $project = $this->getProject($request->get('project-to-allow'));

        if (! $project) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Project does not exist')
            );
            return;
        }

        $this->big_object_authorization_manager->authorizeProject($project);
        $this->system_event_manager->queueProjectsConfigurationUpdate([$project->getID()]);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-git', 'Project successfully added')
        );
    }

    public function display(Codendi_Request $request)
    {
        $title         = dgettext('tuleap-git', 'Git');
        $template_path = dirname(GIT_BASE_DIR) . '/templates';

        $GLOBALS['HTML']->addJavascriptAsset($this->asset);

        $admin_presenter = new Git_AdminGitoliteConfigPresenter(
            $title,
            $this->csrf,
            $this->big_object_authorization_manager->getAuthorizedProjects(),
        );

        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            $template_path,
            'admin-plugin',
            $admin_presenter
        );
    }
}
