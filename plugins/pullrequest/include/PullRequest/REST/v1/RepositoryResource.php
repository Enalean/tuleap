<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use GitDao;
use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceDAO;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\PullRequest\REST\v1\RepositoryPullRequests\GETHandler;
use Tuleap\PullRequest\REST\v1\RepositoryPullRequests\QueryToSearchCriteriaConverter;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use UserManager;

class RepositoryResource
{
    /**
     * @throws RestException
     */
    public function getPaginatedPullRequests(GitRepository $repository, $query, $limit, $offset): RepositoryPullRequestRepresentation
    {
        return $this->getGETHandler()
            ->andThen(function ($get_handler) use ($repository, $query, $limit, $offset) {
                return $get_handler->handle($repository, $query, $limit, $offset);
            })
            ->match(
                static fn(RepositoryPullRequestRepresentation $representation) => $representation,
                FaultMapper::mapToRestException(...)
            );
    }

    /**
     * @return Ok<GETHandler> | Err<Fault>
     */
    private function getGETHandler(): Ok | Err
    {
        $git_plugin = \PluginFactory::instance()->getPluginByName('git');
        if (! $git_plugin) {
            return Result::err(Fault::fromMessage("Pullrequest plugin cannot find git plugin"));
        }

        $gitolite_access_URL_generator = new GitoliteAccessURLGenerator($git_plugin->getPluginInfo());

        $user_manager           = UserManager::instance();
        $pull_request_dao       = new PullRequestDao();
        $git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );
        $get_handler            = new GETHandler(
            new QueryToSearchCriteriaConverter(),
            $pull_request_dao,
            $user_manager,
            $git_repository_factory,
            $git_repository_factory,
            new GitPullRequestReferenceRetriever(new GitPullRequestReferenceDAO()),
            new ReviewerRetriever(
                $user_manager,
                new ReviewerDAO(),
                new PullRequestPermissionChecker(
                    $git_repository_factory,
                    new \Tuleap\Project\ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        \EventManager::instance()
                    ),
                    new AccessControlVerifier(
                        new FineGrainedRetriever(new FineGrainedDao()),
                        new \System_Command()
                    )
                )
            ),
            $gitolite_access_URL_generator,
            \pullrequestPlugin::getLogger(),
        );

        return Result::ok($get_handler);
    }
}
