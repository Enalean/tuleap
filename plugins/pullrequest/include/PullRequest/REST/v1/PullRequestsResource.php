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
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\Comment\Factory as CommentFactory;
use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Tuleap\PullRequest\Exception\PullRequestNotCreatedException;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\Exception\UnknownReferenceException;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\REST\MinimalUserRepresentation;
use GitRepositoryFactory;
use GitDao;
use ProjectManager;
use UserManager;
use PFUser;
use GitRepository;
use Git_Command_Exception;
use URLVerification;
use Tuleap\REST\ProjectAuthorization;
use BackendLogger;

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

    /** @var Tuleap\PullRequest\PullRequestCloser */
    private $pull_request_closer;

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

        $this->user_manager        = UserManager::instance();
        $this->pull_request_closer = new PullRequestCloser($pull_request_dao);
        $this->logger              = new BackendLogger();
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        return $this->sendAllowHeadersForPullRequests();
    }

    /**
     * Get pull request
     *
     * Retrieve a given pull request. <br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws 403
     * @throws 404 x Pull request does not exist
     */
    protected function get($id) {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

        $this->checkUserCanReadRepository($user, $git_repository);

        $pull_request_representation = new PullRequestRepresentation();
        $pull_request_representation->build($pull_request, $git_repository);

        return $pull_request_representation;
    }

    /**
     * Get pull request's impacted files
     *
     * Get the impacted files for a pull request.<br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/files
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @return array {@type PullRequest\REST\v1\PullRequestFileRepresentation}
     *
     * @throws 403
     * @throws 404 x Pull request does not exist
     */
    protected function getFiles($id) {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());
        $executor       = $this->getExecutor($git_repository);

        $this->checkUserCanReadRepository($user, $git_repository);

        $file_representation_factory = new PullRequestFileRepresentationFactory($executor);

        try {
            $modified_files = $file_representation_factory->getModifiedFilesRepresentations($pull_request);
        } catch (UnknownReferenceException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        return $modified_files;
    }

    /**
     * Get the contents of a given file in a pull request
     *
     * Get the contents of a given file for the source branch and the dest branch for a pull request.<br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/file_content
     *
     * @access protected
     *
     * @param  int $id pull request ID
     * @param  string $path File path {@from query}
     *
     * @return PullRequestFileContentRepresentation {@type Tuleap\PullRequest\REST\v1\PullRequestFileContentRepresentation}
     *
     * @throws 403
     * @throws 404 x Pull request does not exist
     * @throws 404 x The file does not exist
     */
    protected function getFileContent($id, $path) {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

        $this->checkUserCanReadRepository($user, $git_repository);

        $executor     = $this->getExecutor($git_repository);
        $dest_content = $this->getDestinationContent($pull_request, $executor, $path);
        $src_content  = $this->getSourceContent($pull_request, $executor, $path);

        if ($src_content === null && $dest_content === null) {
            throw new RestException(404, 'The file does not exist');
        }

        $file_content_representation = new PullRequestFileContentRepresentation();
        $file_content_representation->build($dest_content, $src_content);

        return $file_content_representation;
    }

    private function getSourceContent(PullRequest $pull_request, GitExec $executor, $path) {
        try {
            $src_content  = $executor->getFileContent($pull_request->getSha1Src(), $path);
        } catch (Git_Command_Exception $exception) {
            $src_content = null;
        }

        return $src_content;
    }

    private function getDestinationContent(PullRequest $pull_request, GitExec $executor, $path) {
        try {
            $dest_content  = $executor->getFileContent($pull_request->getSha1Dest(), $path);
        } catch (Git_Command_Exception $exception) {
            $dest_content = null;
        }

        return $dest_content;
    }

    /**
     * Create PullRequest
     *
     * Create a new pullrequest.<br/>
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     * <br/>
     * Here is an example of a valid POST content:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"repository_id": 3,<br/>
     * &nbsp;&nbsp;"branch_src": "dev",<br/>
     * &nbsp;&nbsp;"branch_dest": "master"<br/>
     * }<br/>
     * </pre>
     *
     * @url POST
     *
     * @access protected
     *
     * @param  PullRequestPOSTRepresentation $content Id of the Git repository, name of the source branch and name of the destination branch
     * @return PullRequestReference
     *
     * @throws 400
     * @throws 403
     * @throws 404
     * @status 201
     */
    protected function post(PullRequestPOSTRepresentation $content) {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $repository_id  = $content->repository_id;
        $branch_src     = $content->branch_src;
        $branch_dest    = $content->branch_dest;
        $user           = $this->user_manager->getCurrentUser();
        $git_repository = $this->getRepository($repository_id);

        $this->checkUserCanReadRepository($user, $git_repository);

        $executor = $this->getExecutor($git_repository);

        try {
            $sha1_src     = $executor->getReferenceBranch($branch_src);
            $sha1_dest    = $executor->getReferenceBranch($branch_dest);
            $pull_request = $this->pull_request_factory->create(
                $git_repository,
                $user,
                $branch_src,
                $sha1_src,
                $branch_dest,
                $sha1_dest
            );
        } catch (UnknownBranchNameException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestNotCreatedException $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        $pull_request_reference = new PullRequestReference();
        $pull_request_reference->build($pull_request);

        $this->sendLocationHeader($pull_request_reference->uri);

        return $pull_request_reference;
    }

    /**
     * Partial update of a pull request
     *
     * Merge or abandon a pull request.
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     * <br/>
     *
     * Here is an example of a valid PATCH content to merge a pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"status": "merge"<br/>
     * }<br/>
     * </pre>
     * <br/>
     *
     * For now, only fast-forward merges are taken into account.
     * <br/>
     *
     * A pull request that has been abandoned cannot be merged later.<br/>
     * Here is an example of a valid PATCH content to abandon a pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"status": "abandon"<br/>
     * }<br/>
     * </pre>
     *
     * @url PATCH {id}
     *
     * @access protected
     *
     * @param  int $id pull request ID
     * @param  PullRequestPATCHRepresentation $body new pull request status {@from body}
     *
     * @throws 400
     * @throws 403
     * @throws 404 x Pull request does not exist
     * @throws 500 x Error while abandoning the pull request
     * @throws 500 x Error while merging the pull request
     */
    protected function patch($id, PullRequestPATCHRepresentation $body) {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

        $this->checkUserCanWriteRepository($user, $git_repository);

        $status = $body->status;
        switch ($status) {
            case PullRequestRepresentation::STATUS_ABANDON:
                try {
                    $this->abandon($pull_request);
                } catch (PullRequestCannotBeAbandoned $exception) {
                    throw new RestException(400, $exception->getMessage());
                }
                break;
            case PullRequestRepresentation::STATUS_MERGE:
                try {
                    $this->pull_request_closer->fastForwardMerge(
                        $git_repository,
                        $pull_request
                    );
                } catch (PullRequestCannotBeMerged $exception) {
                    throw new RestException(400, $exception->getMessage());
                } catch (Git_Command_Exception $exception) {
                    $this->logger->error('Error while merging the pull request -> ' . $exception->getMessage());
                    throw new RestException(500, 'Error while merging the pull request');
                }
                break;
            default:
                throw new RestException(
                    400,
                    'Cannot deal with provided status. Supported statuses are ' . PullRequestRepresentation::STATUS_MERGE . ', '. PullRequestRepresentation::STATUS_ABANDON
                );
        }
    }

    private function abandon(PullRequest $pull_request) {
        if (! $this->pull_request_closer->abandon($pull_request)) {
            throw new RestException(500, 'Error while abandoning the pull request');
        }
    }

    /**
     * @url OPTIONS {id}/comments
     */
    public function optionsComments($id) {
        return $this->sendAllowHeadersForComments();
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
        $this->sendAllowHeadersForComments();

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
        $this->checkAccess();
        $this->sendAllowHeadersForComments();

        $user           = $this->user_manager->getCurrentUser();
        $pull_request   = $this->getPullRequest($id);
        $git_repository = $this->getRepository($pull_request->getRepositoryId());

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
            throw new RestException(404, "Git repository not found");
        }

        return $repository;
    }

    private function checkUserCanReadRepository(PFUser $user, GitRepository $repository) {
        ProjectAuthorization::userCanAccessProject($user, $repository->getProject(), new URLVerification());

        if (! $repository->userCanRead($user)) {
            throw new RestException(403, 'User is not able to READ the git repository');
        }
    }

    private function checkUserCanWriteRepository(PFUser $user, GitRepository $repository) {
        ProjectAuthorization::userCanAccessProject($user, $repository->getProject(), new URLVerification());

        if (! $repository->userCanWrite($user)) {
            throw new RestException(403, 'User is not able to WRITE the git repository');
        }
    }

    private function sendLocationHeader($uri) {
        $uri_with_api_version = '/api/v1/' . $uri;

        Header::Location($uri_with_api_version);
    }

    private function sendAllowHeadersForPullRequests() {
        Header::allowOptionsGetPostPatch();
    }

    private function sendAllowHeadersForComments() {
        HEADER::allowOptionsGetPost();
    }

    /**
     * @return GitExec
     */
    private function getExecutor(GitRepository $git_repository) {
        return new GitExec($git_repository->getFullPath(), $git_repository->getFullPath());
    }
}
