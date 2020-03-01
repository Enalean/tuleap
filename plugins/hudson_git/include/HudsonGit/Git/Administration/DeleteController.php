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
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class DeleteController implements DispatchableWithRequest
{
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;

    /**
     * @var JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var JenkinsServerDeleter
     */
    private $jenkins_server_deleter;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        GitPermissionsManager $git_permissions_manager,
        JenkinsServerFactory $jenkins_server_factory,
        JenkinsServerDeleter $jenkins_server_deleter,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->git_permissions_manager = $git_permissions_manager;
        $this->jenkins_server_factory  = $jenkins_server_factory;
        $this->jenkins_server_deleter  = $jenkins_server_deleter;
        $this->csrf_token              = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->exist('jenkins_server_id')) {
            throw new RuntimeException(dgettext("tuleap-hudson_git", "Expected Jenkins server ID not found"));
        }

        $jenkins_server_id = (int) $request->get("jenkins_server_id");
        $jenkins_server    = $this->jenkins_server_factory->getJenkinsServerById($jenkins_server_id);

        if ($jenkins_server === null) {
            throw new NotFoundException(dgettext("tuleap-git", "Jenkins server not found."));
        }

        $project = $jenkins_server->getProject();
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

        $this->jenkins_server_deleter->deleteServer($jenkins_server);

        $layout->addFeedback(
            Feedback::INFO,
            dgettext("tuleap-hudson_git", "The Jenkins server has successfully been Removed.")
        );

        $layout->redirect(
            URLBuilder::buildUrl($project)
        );
    }
}
