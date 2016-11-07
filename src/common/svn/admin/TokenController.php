<?php
/**
 * Copyright (c) Enalean, 2014-2016. All Rights Reserved.
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

namespace Tuleap\SvnCore\Admin;

use CSRFSynchronizerToken;
use Event;
use EventManager;
use Feedback;
use HTTPRequest;
use ProjectManager;
use SVN_TokenUsageManager;

class TokenController implements Controller
{
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

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ProjectManager $project_manager,
        SVN_TokenUsageManager $token_manager,
        EventManager $event_manager,
        Renderer $renderer,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->project_manager = $project_manager;
        $this->token_manager   = $token_manager;
        $this->event_manager   = $event_manager;
        $this->renderer        = $renderer;
        $this->csrf_token      = $csrf_token;
    }

    public function process(HTTPRequest $request)
    {
        if ($request->isPost()) {
            $this->processFormSubmission($request);
        }
        $this->display();
    }

    private function display()
    {
        $presenter = new TokenPresenter(
            $this->token_manager->getProjectsAuthorizingTokens(),
            true,
            $this->csrf_token
        );

        $this->renderer->renderANoFramedPresenter(
            $presenter
        );
    }

    private function processFormSubmission(HTTPRequest $request)
    {
        if ($request->get('action') === 'update_project') {
            $this->updateProjectTokenUsage($request);
        }
    }

    private function updateProjectTokenUsage(HTTPRequest $request)
    {
        $this->csrf_token->check();

        $project_to_add = $request->get('project-to-allow');
        if ($request->get('allow-project') && !empty($project_to_add)) {
            $this->allowSVNTokensForProject($project_to_add);
        }

        $project_ids_to_remove = $request->get('project-ids-to-revoke');
        if ($request->get('revoke-project') && !empty($project_ids_to_remove)) {
            $this->revokeProjectsAuthorization($project_ids_to_remove);
        }

        $GLOBALS['Response']->redirect('/admin/svn/index.php?pane=token');
    }

    private function revokeProjectsAuthorization(array $project_ids_to_remove)
    {
        if (count($project_ids_to_remove) > 0 &&
            $this->token_manager->removeProjectsAuthorizationForTokens($project_ids_to_remove)) {
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

    private function allowSVNTokensForProject($project_to_migrate)
    {
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

    private function sendUpdateProjectListError()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('svn_tokens', 'allowed_project_update_project_list_error')
        );
    }
}
