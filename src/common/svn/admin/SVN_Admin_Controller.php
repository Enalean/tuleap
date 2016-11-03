<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class SVN_Admin_Controller {

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var SVN_TokenUsageManager
     */
    private $token_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        ProjectManager $project_manager,
        SVN_TokenUsageManager $token_manager,
        EventManager $event_manager
    ) {
        $this->project_manager   = $project_manager;
        $this->token_manager     = $token_manager;
        $this->event_manager     = $event_manager;
    }

    public function getAdminIndex(HTTPRequest $request) {
        $this->checkAccess($request);

        $presenter = new SVN_Admin_AllowedProjectsPresenter(
            $this->token_manager->getProjectsAuthorizingTokens(),
            true
        );

        $renderer = new AdminPageRenderer();
        $renderer->renderAPresenter(
            $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_title'),
            ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor',
            $presenter::TEMPLATE,
            $presenter
        );
    }

    private function checkAccess(HTTPRequest $request) {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $GLOBALS['Response']->redirect('/');
        }
    }

    public function updateProject(HTTPRequest $request) {
        $token = new CSRFSynchronizerToken('/admin/svn/svn_tokens.php?action=update_project');
        $token->check();

        $project_to_add  = $request->get('project-to-allow');
        if ($request->get('allow-project') && !empty($project_to_add)) {
            $this->allowSVNTokensForProject($project_to_add);
        }

        $project_ids_to_remove = $request->get('project-ids-to-revoke');
        if ($request->get('revoke-project') && ! empty($project_ids_to_remove)) {
            $this->revokeProjectsAuthorization($project_ids_to_remove);
        }

        $GLOBALS['Response']->redirect('/admin/svn/svn_tokens.php?action=index');
    }

    private function revokeProjectsAuthorization(array $project_ids_to_remove) {
        if (count($project_ids_to_remove) > 0 &&
            $this->token_manager->removeProjectsAuthorizationForTokens($project_ids_to_remove)
        ){
            $this->event_manager->processEvent(
                Event::SVN_REVOKE_TOKENS,
                array('project_ids' => implode(',', $project_ids_to_remove))
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_revoke_project')
            );

        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function allowSVNTokensForProject($project_to_migrate) {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_migrate);

        if ($project && $this->token_manager->canAuthorizeTokens($project)) {

            $this->token_manager->setProjectAuthorizesTokens($project);

            $this->event_manager->processEvent(
                Event::SVN_AUTHORIZE_TOKENS,
                array('group_id' => $project->getID())
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_allow_project')
            );
        } else {
            $this->sendUpdateProjectListError();
        }
    }

    private function sendUpdateProjectListError() {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_update_project_list_error')
        );
    }
}
