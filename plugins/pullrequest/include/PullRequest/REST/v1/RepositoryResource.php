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

use Exception;
use GitDao;
use GitRepoNotFoundException;
use GitRepoNotReadableException;
use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\PullRequest\REST\v1\Authors\RepositoryPullRequestsAuthorsRepresentation;
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
use Tuleap\PullRequest\REST\v1\Reviewers\RepositoryPullRequestsReviewersRepresentation;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

class RepositoryResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    /**
     * @url OPTIONS {id}/pull_requests
     *
     * @param int $id Id of the repository
     */
    public function optionsPullRequests(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get pull requests
     *
     * <p>Retrieve all git repository's pull requests.</p>
     *
     * <p>Pull requests are sorted by descending order of creation date.</p>
     *
     * <p>User is not able to see a pull request in a git repository where he is not able to READ.</p>
     *
     * <p>
     *     <code>$query</code> parameter is optional, by default we return all pull requests.
     * </p>
     * <p>
     *     You can filter on:
     *     <p>
     *         <b>Status</b>: <code>query={"status":"open"}</code> OR <code>query={"status":"closed"}</code>. When not specified, pull-requests with any statuses will be returned.
     *     </p>
     *     <p>
     *         <b>Author</b>: <code>query={"authors": [{"id": int}]}</code> where "id" is the user_id of the author.
     *     </p>
     *     <p>
     *         <b>Labels</b>: <code>query={"labels": [{"id": int}]}</code> where "id" is the id of the label.
     *         The search on labels is additive. It will retrieve only pull-requests having all the specified labels.
     *     </p>
     *     <p>
     *         <b>Search</b>: <code>query={"search": [{"keyword": string}]}</code> where "keyword" is the keyword to find in the pull-requests titles or descriptions.
     *         The search on keywords is additive. It will retrieve only pull-requests whose titles AND/OR descriptions contain ALL the provided keywords.
     *     </p>
     *     <p>
     *         <b>Target branch</b>: <code>query={"target_branches": [{"name": string}]}</code> where "name" is the name of the target branch.
     *     </p>
     *     <p>
     *         <b>Reviewer</b>: <code>query={"reviewers": [{"id": int}]}</code> where "id" is the user_id of the reviewer.
     *     </p>
     *     <p>
     *         <b>Related to</b>: <code>query={"related_to": [{"id": int}]}</code> where "id" is the user_id of the user.
     *         <br>It will return all the pull-requests on which the user is author OR reviewer.
     *         <br>NOTE: You cannot combine the related_to filter with the authors or the reviewers filters.
     *     </p>
     * </p>
     *
     * <p>
     *     All these filters are cumulative. For instance, <code>query={"status": "closed", "authors": [{"id": 102 }]}</code>
     *     will return all the closed pull-requests whose author is user 102.
     * </p>
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/pull_requests
     *
     * @access protected
     *
     * @param int $id       Id of the repository
     * @param string $query JSON object of search criteria properties {@from path}
     * @param string $order Sort order by pull request creation date {@from path}{@choice asc,desc}
     * @psalm-param "asc"|"desc" $order
     * @param int $limit    Number of elements displayed per page {@from path} {@min 0} {@max 50}
     * @param int $offset   Position of the first element to display {@from path} {@min 0}
     *
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getPullRequests(int $id, string $query = '', string $order = 'desc', int $limit = self::MAX_LIMIT, int $offset = 0): RepositoryPullRequestRepresentation
    {
        $this->checkAccess();

        $repository = $this->getRepositoryUserCanSee($id);

        return $this->getGETHandler()
            ->andThen(function ($get_handler) use ($repository, $query, $order, $limit, $offset) {
                return $get_handler->handle($repository, $query, $order, $limit, $offset);
            })
            ->match(
                function (RepositoryPullRequestRepresentation $representation) use ($limit, $offset) {
                    Header::allowOptionsGet();
                    $this->sendPaginationHeaders($limit, $offset, $representation->total_size);

                    return $representation;
                },
                FaultMapper::mapToRestException(...)
            );
    }

    /**
     * @return Ok<GETHandler> | Err<Fault>
     */
    private function getGETHandler(): Ok|Err
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

    /**
     * @url OPTIONS {id}/pull_requests_authors
     *
     * @param int $id Id of the repository
     */
    public function optionsPullRequestsAuthors(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get pull requests authors in a given git repository
     *
     * @url GET {id}/pull_requests_authors
     *
     * @access protected
     *
     * @param int $id     Id of the repository
     * @param int $limit  Number of elements displayed per page {@from path} {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@from path} {@min 0}
     *
     * @return MinimalUserRepresentation[]
     * @throws RestException
     */
    public function getPullRequestsAuthors(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $repository = $this->getRepositoryUserCanSee($id);

        return (
            new \Tuleap\PullRequest\REST\v1\Authors\GETHandler(
                UserManager::instance(),
                new PullRequestDao(),
            )
        )->handle($repository, $limit, $offset)->match(
            function (RepositoryPullRequestsAuthorsRepresentation $representations) use ($limit, $offset) {
                Header::allowOptionsGet();
                $this->sendPaginationHeaders($limit, $offset, $representations->total_size);

                return $representations->collection;
            },
            FaultMapper::mapToRestException(...)
        );
    }

    /**
     * @url OPTIONS {id}/pull_requests_reviewers
     *
     * @param int $id Id of the repository
     */
    public function optionsPullRequestsReviewers(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get pull requests reviewers in a given git repository
     *
     * @url GET {id}/pull_requests_reviewers
     *
     * @access protected
     *
     * @param int $id Id of the repository
     * @param int $limit Number of elements displayed per page {@from path} {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@from path} {@min 0}
     *
     * @return MinimalUserRepresentation[]
     * @throws RestException
     */
    public function getPullRequestsReviewers(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $repository = $this->getRepositoryUserCanSee($id);

        return (
            new \Tuleap\PullRequest\REST\v1\Reviewers\GETHandler(
                UserManager::instance(),
                new ReviewerDAO()
            )
        )->handle($repository, $limit, $offset)->match(
            function (RepositoryPullRequestsReviewersRepresentation $representation) use ($limit, $offset) {
                Header::allowOptionsGet();
                $this->sendPaginationHeaders($limit, $offset, $representation->total_size);

                return $representation->collection;
            },
            FaultMapper::mapToRestException(...)
        );
    }

    /**
     * @throws RestException
     */
    private function getRepositoryUserCanSee(int $repository_id): GitRepository
    {
        $user_manager = UserManager::instance();
        if ($user_manager === null) {
            throw new RestException(500);
        }

        $repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        try {
            $repository = $repository_factory->getRepositoryByIdUserCanSee(
                $user_manager->getCurrentUser(),
                $repository_id
            );
        } catch (GitRepoNotReadableException $exception) {
            throw new RestException(403, 'Git repository not accessible for user');
        } catch (GitRepoNotFoundException $exception) {
            throw new RestException(404, 'Git repository not found');
        } catch (Exception $exception) {
            throw new RestException(403, 'Project not accessible for user');
        }

        return $repository;
    }

    private function sendPaginationHeaders(int $limit, int $offset, int $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
