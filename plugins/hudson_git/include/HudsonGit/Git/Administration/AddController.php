<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HudsonGit\Git\Administration;

use CSRFSynchronizerToken;
use Feedback;
use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use ProjectManager;
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class AddController implements DispatchableWithRequest
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;

    /**
     * @var JenkinsServerAdder
     */
    private $jenkins_server_adder;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ProjectManager $project_manager,
        GitPermissionsManager $git_permissions_manager,
        JenkinsServerAdder $jenkins_server_adder,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->project_manager         = $project_manager;
        $this->git_permissions_manager = $git_permissions_manager;
        $this->jenkins_server_adder    = $jenkins_server_adder;
        $this->csrf_token              = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProjectFromRequest($request);
        $this->csrf_token->check(
            URLBuilder::buildUrl($project)
        );

        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        $user = $request->getCurrentUser();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new ForbiddenException(dgettext("tuleap-hudson_git", 'User is not Git administrator.'));
        }

        $provided_url = $request->get('url');
        if ($provided_url === false) {
            throw new RuntimeException(dgettext("tuleap-hudson_git", "Expected Jenkins server URL not found"));
        }

        try {
            $this->jenkins_server_adder->addServerInProject(
                $project,
                trim($provided_url)
            );

            $layout->addFeedback(
                Feedback::INFO,
                dgettext("tuleap-hudson_git", "The Jenkins server has successfully been added.")
            );
        } catch (JenkinsServerAlreadyDefinedException $exception) {
            $layout->addFeedback(
                Feedback::WARN,
                dgettext("tuleap-hudson_git", "The Jenkins server is already defined in project.")
            );
        } catch (JenkinsServerURLNotValidException $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext("tuleap-hudson_git", "The Jenkins server URL provided is not well formed.")
            );
        } finally {
            $layout->redirect(
                URLBuilder::buildUrl($project)
            );
        }
    }

    /**
     * @throws NotFoundException
     */
    private function getProjectFromRequest(HTTPRequest $request): Project
    {
        $project_id = $request->get('project_id');
        if ($project_id === false) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        $project = $this->project_manager->getProject((int) $project_id);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        return $project;
    }
}
