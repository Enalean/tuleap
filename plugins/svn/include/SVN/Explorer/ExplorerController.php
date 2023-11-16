<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Explorer;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Project;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\SVN\Repository\Exception\CannotCreateRepositoryException;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVN\Repository\Exception\UserIsNotSVNAdministratorException;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\ServiceSvn;
use Tuleap\SVN\SvnPermissionManager;

class ExplorerController
{
    public const NAME = 'explorer';

    /** @var SvnPermissionManager */
    private $permissions_manager;

    /** @var RepositoryManager */
    private $repository_manager;
    /**
     * @var RepositoryBuilder
     */
    private $repository_builder;
    /**
     * @var RepositoryCreator
     */
    private $repository_creator;

    public function __construct(
        RepositoryManager $repository_manager,
        SvnPermissionManager $permissions_manager,
        RepositoryBuilder $repository_builder,
        RepositoryCreator $repository_creator,
    ) {
        $this->repository_manager  = $repository_manager;
        $this->permissions_manager = $permissions_manager;
        $this->repository_builder  = $repository_builder;
        $this->repository_creator  = $repository_creator;
    }

    public function index(ServiceSvn $service, HTTPRequest $request)
    {
        $this->renderIndex($service, $request);
    }

    private function renderIndex(ServiceSvn $service, HTTPRequest $request): void
    {
        $project = $request->getProject();
        $token   = $this->generateTokenForCeateRepository($request->getProject());

        $repository_list = $this->repository_manager->getRepositoriesInProjectWithLastCommitInfo($request->getProject());
        $repositories    = $this->repository_builder->build($repository_list, $request->getCurrentUser());
        $is_admin        = $this->permissions_manager->isAdmin($project, $request->getCurrentUser());

        $GLOBALS['HTML']->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../scripts/main/frontend-assets',
            '/assets/svn/main'
        );
        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptAsset($include_assets, 'homepage.js'));

        $service->renderInPage(
            $request,
            'Welcome',
            'explorer/index',
            new ExplorerPresenter(
                $project,
                $token,
                $repositories,
                $is_admin
            )
        );
    }

    private function generateTokenForCeateRepository(Project $project)
    {
        return new CSRFSynchronizerToken(SVN_BASE_URL . "/?group_id=" . $project->getid() . '&action=createRepo');
    }

    public function createRepository(HTTPRequest $request, \PFUser $user)
    {
        $token = $this->generateTokenForCeateRepository($request->getProject());
        $token->check();

        $repo_name = $request->get("repo_name");

        $repository_to_create = SvnRepository::buildToBeCreatedRepository($repo_name, $request->getProject());
        try {
            $this->repository_creator->create($repository_to_create, $user);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(
                    dgettext('tuleap-svn', 'Repository %s successfully created'),
                    $repo_name
                )
            );
        } catch (CannotCreateRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-svn', 'Unable to create repository %s'),
                    $repo_name
                )
            );
        } catch (UserIsNotSVNAdministratorException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-svn', "User doesn't have permission to create a repository"));
        } catch (RepositoryNameIsInvalidException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-svn', 'The repository name %s is invalid'),
                    $repo_name
                )
            );
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
        }
        $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?' . http_build_query(['group_id' => $request->getProject()->getid()]));
    }
}
