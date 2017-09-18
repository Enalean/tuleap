<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\PullRequest\Authorization\AccessControlVerifier;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\Logger;
use Tuleap\PullRequest\Exception\MalformedQueryParameterException;
use Luracast\Restler\RestException;
use GitRepositoryFactory;
use GitRepository;
use ProjectManager;
use UserManager;
use Git_Command_Exception;
use GitDao;
use ReferenceManager;

class RepositoryResource
{

    /** @var Tuleap\PullRequest\Dao */
    private $pull_request_dao;

    /** @var Tuleap\PullRequest\Factory */
    private $pull_request_factory;

    /** @var GitRepositoryFactory */
    private $git_repository_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var QueryToCriterionConverter */
    private $query_to_criterion_converter;

    /**
     * @var Tuleap\PullRequest\Logger
     */
    private $logger;

    public function __construct()
    {
        $this->pull_request_dao       = new PullRequestDao();
        $this->pull_request_factory   = new PullRequestFactory($this->pull_request_dao, ReferenceManager::instance());
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );
        $this->user_manager                 = UserManager::instance();
        $this->query_to_criterion_converter = new QueryToCriterionConverter();

        $this->access_control_verifier = new AccessControlVerifier(
            new FineGrainedRetriever(new FineGrainedDao()),
            new \System_Command()
        );

        $this->logger = new Logger();
    }

    public function getPaginatedPullRequests(GitRepository $repository, $query, $limit, $offset)
    {
        try {
            $criterion = $this->query_to_criterion_converter->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $result     = $this->pull_request_dao->getPaginatedPullRequests($repository->getId(), $criterion, $limit, $offset);
        $user       = $this->user_manager->getCurrentUser();
        $total_size = (int) $this->pull_request_dao->foundRows();

        $collection = array();
        foreach ($result as $row) {
            $pull_request      = $this->pull_request_factory->getInstanceFromRow($row);

            $repository_src  = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
            $repository_dest = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());

            $executor                  = new GitExec($repository_src->getFullPath(), $repository_src->getFullPath());
            $pr_representation_factory = new PullRequestRepresentationFactory($executor, $this->access_control_verifier);

            try {
                $pull_request_representation = $pr_representation_factory->getPullRequestRepresentation(
                    $pull_request,
                    $repository_src,
                    $repository_dest,
                    $user
                );

                $collection[] = $pull_request_representation;
            } catch (Git_Command_Exception $exception) {
                $pull_request_id = $pull_request->getId();
                $this->logger->warn("The pullrequest #$pull_request_id cannot be displayed because of a missing reference");

                continue;
            }
        }

        $representation = new RepositoryPullRequestRepresentation();
        $representation->build(
            $collection,
            $total_size
        );

        return $representation;
    }
}
