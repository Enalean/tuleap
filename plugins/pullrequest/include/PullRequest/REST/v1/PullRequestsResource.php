<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Luracast\Restler\RestException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Comment\Factory as CommentFactory;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\PullRequestNotFoundException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\REST\MinimalUserRepresentation;
use GitRepositoryFactory;
use GitDao;
use ProjectManager;
use UserManager;
use PFUser;
use GitRepository;
use URLVerification;
use Tuleap\REST\ProjectAuthorization;

class PullRequestsResource extends AuthenticatedResource {

    const MAX_LIMIT = 50;

    /** @var GitRepositoryFactory */
    private $git_repository_factory;

    /** @var Tuleap\PullRequest\Factory */
    private $pull_request_factory;

    /** @var Tuleap\PullRequest\Comment\Factory */
    private $comment_factory;

    /** @var PaginatedCommentsRepresentationsBuilder */
    private $paginated_comments_representations_builder;

    /** @var UserManager */
    private $user_manager;


    public function __construct() {
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $pull_request_dao           = new PullRequestDao();
        $this->pull_request_factory = new PullRequestFactory($pull_request_dao);

        $comment_dao           = new CommentDao();
        $this->comment_factory = new CommentFactory($comment_dao);

        $this->paginated_comments_representations_builder = new PaginatedCommentsRepresentationsBuilder(
            $this->comment_factory
        );

        $this->user_manager = UserManager::instance();
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        return Header::allowOptionsGet();
    }

    /**
     * Get PullRequest
     *
     * Retrieve a given pullrequest. <br/>
     * User is not able to see a pullrequest in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}
     *
     * @access protected
     *
     * @param int $id Pull request Id
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function get($id) {
        $this->checkAccess();

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

        $this->checkUserCanReadRepository($user, $git_repository);

        $pull_request_representation = new PullRequestRepresentation();
        $pull_request_representation->build($pull_request, $git_repository);

        return $pull_request_representation;
    }

    /**
     * @url OPTIONS {id}/comments
     */
    public function optionsComments() {
        return Header::allowOptionsGetPost();
    }

    /**
     * Get pull request's comments
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/comments
     *
     * @access protected
     *
     * @param int    $id     Pull request id
     * @param int    $limit  Number of fetched comments {@from path}
     * @param int    $offset Position of the first comment to fetch {@from path}
     * @param string $order  In which order comments are fetched. Default is asc. {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\CommentRepresentation}
     *
     * @throws 404
     * @throws 406
     */
    protected function getComments($id, $limit = 10, $offset = 0, $order = 'asc') {
        $this->checkAccess();
        $this->checkLimit($limit);

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

        $this->checkUserCanReadRepository($user, $git_repository);

        $paginated_comments_representations = $this->paginated_comments_representations_builder->getPaginatedCommentsRepresentations(
            $id,
            $limit,
            $offset,
            $order
        );

        Header::sendPaginationHeaders($limit, $offset, $paginated_comments_representations->getTotalSize(), self::MAX_LIMIT);

        return $paginated_comments_representations->getCommentsRepresentations();
    }

    /**
     * Post a new comment
     *
     * Post a new comment for a given pull request <br>
     * Format: { "content": "My new comment" }
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url POST {id}/comments
     *
     * @access protected
     *
     * @param int                       $id           Pull request id
     * @param CommentPOSTRepresentation $comment_data Comment {@from body} {@type Tuleap\PullRequest\REST\v1\CommentPOSTRepresentation}
     *
     * @status 201
     */
    protected function postComments($id, CommentPOSTRepresentation $comment_data) {
        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

        $this->checkAccess();
        $this->checkUserCanReadRepository($user, $git_repository);

        $comment        = new Comment(0, $id, $user->getId(), $comment_data->content);
        $new_comment_id = $this->comment_factory->save($comment);

        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($user);

        $comment_representation = new CommentRepresentation();
        $comment_representation->build($new_comment_id, $user_representation, $comment_data->content);

        return $comment_representation;
    }

    private function checkLimit($limit) {
        if ($limit > self::MAX_LIMIT) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function getPullRequest($pull_request_id) {
        try {
            return $this->pull_request_factory->getPullRequestById($pull_request_id);

        } catch (PullRequestNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
    }

    private function getRepository($repository_id) {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new RestException(404, "The git repository where the pull request was generated does not exist");
        }

        return $repository;
    }

    private function checkUserCanReadRepository(PFUser $user, GitRepository $repository) {
        ProjectAuthorization::userCanAccessProject($user, $repository->getProject(), new URLVerification());

        if (! $repository->userCanRead($user)) {
            throw new RestException(403, 'User is not able to READ the git repository');
        }
    }

}
