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
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Exception\MalformedQueryParameterException;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceDAO;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersRepresentation;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use UserManager;

class RepositoryResource
{
    /** @var \Tuleap\PullRequest\Dao */
    private $pull_request_dao;

    /** @var \Tuleap\PullRequest\Factory */
    private $pull_request_factory;

    /** @var GitRepositoryFactory */
    private $git_repository_factory;

    /** @var QueryToCriterionConverter */
    private $query_to_criterion_converter;

    /**
     * @var GitoliteAccessURLGenerator
     */
    private $gitolite_access_URL_generator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private GitPullRequestReferenceRetriever $git_pull_request_reference_retriever;
    private ReviewerRetriever $reviewer_retriever;

    public function __construct()
    {
        $this->pull_request_dao             = new PullRequestDao();
        $this->pull_request_factory         = new PullRequestFactory($this->pull_request_dao, ReferenceManager::instance());
        $this->git_repository_factory       = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );
        $this->query_to_criterion_converter = new QueryToCriterionConverter();

        $git_plugin = \PluginFactory::instance()->getPluginByName('git');
        if (! $git_plugin) {
            throw new \Exception("Pullrequest plugin cannot find git plugin");
        }
        $this->gitolite_access_URL_generator        = new GitoliteAccessURLGenerator($git_plugin->getPluginInfo());
        $this->git_pull_request_reference_retriever = new GitPullRequestReferenceRetriever(new GitPullRequestReferenceDAO());

        $this->logger             = \pullrequestPlugin::getLogger();
        $this->reviewer_retriever = new ReviewerRetriever(
            UserManager::instance(),
            new ReviewerDAO(),
            new PullRequestPermissionChecker(
                $this->git_repository_factory,
                new \Tuleap\Project\ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new AccessControlVerifier(
                    new FineGrainedRetriever(new FineGrainedDao()),
                    new \System_Command()
                )
            )
        );
    }

    public function getPaginatedPullRequests(GitRepository $repository, $query, $limit, $offset): RepositoryPullRequestRepresentation
    {
        try {
            $criterion = $this->query_to_criterion_converter->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $result     = $this->pull_request_dao->getPaginatedPullRequests($repository->getId(), $criterion, $limit, $offset);
        $total_size = $this->pull_request_dao->foundRows();

        $collection = [];
        foreach ($result as $row) {
            $pull_request = $this->pull_request_factory->getInstanceFromRow($row);

            $repository_src  = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
            $repository_dest = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());
            $git_reference   = $this->git_pull_request_reference_retriever->getGitReferenceFromPullRequest(
                $pull_request
            );

            $reviewers                = $this->reviewer_retriever->getReviewers($pull_request);
            $reviewers_representation = ReviewersRepresentation::fromUsers(...$reviewers);

            if ($repository_src && $repository_dest) {
                $pull_request_representation = new PullRequestMinimalRepresentation($this->gitolite_access_URL_generator);
                $pull_request_representation->buildMinimal(
                    $pull_request,
                    $repository_src,
                    $repository_dest,
                    $git_reference,
                    $reviewers_representation->users
                );
                $collection[] = $pull_request_representation;
            } else {
                $this->logger->debug("Repository source or destination not found for pullrequest " . $pull_request->getId());
            }
        }

        return new RepositoryPullRequestRepresentation($collection, $total_size);
    }
}
