<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use EventManager;
use Luracast\Restler\RestException;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use ProjectHistoryDao;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Label\Label;
use Tuleap\Label\PaginatedCollectionsOfLabelsBuilder;
use Tuleap\Label\REST\LabelRepresentation;
use Tuleap\Label\REST\LabelsPATCHRepresentation;
use Tuleap\Label\REST\LabelsUpdater;
use Tuleap\Label\REST\UnableToAddAndRemoveSameLabelException;
use Tuleap\Label\REST\UnableToAddEmptyLabelException;
use Tuleap\Label\UnknownLabelException;
use Tuleap\Project\Label\LabelDao;
use Tuleap\PullRequest\Authorization\AccessControlVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\Comment\Factory as CommentFactory;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceCreator;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceDAO;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNamespaceAvailabilityChecker;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNotFoundException;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\Exception\UnknownReferenceException;
use Tuleap\PullRequest\Label\PullRequestLabelDao;
use Tuleap\PullRequest\Label\LabelsCurlyCoatedRetriever;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\Timeline\Factory as TimelineFactory;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestMerger;
use Tuleap\PullRequest\PullRequestCreator;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileUniDiffBuilder;
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
use \Tuleap\PullRequest\Timeline\Dao as TimelineDao;
use \Tuleap\PullRequest\Timeline\TimelineEventCreator;
use ReferenceManager;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;

class PullRequestsResource extends AuthenticatedResource
{

    const MAX_LIMIT = 50;

    /** @var PullRequestPermissionChecker */
    private $permission_checker;

    /** @var LabelsUpdater */
    private $labels_updater;

    /** @var LabelsCurlyCoatedRetriever */
    private $labels_retriever;

    /** @var GitRepositoryFactory */
    private $git_repository_factory;

    /** @var Tuleap\PullRequest\Factory */
    private $pull_request_factory;

    /** @var Tuleap\PullRequest\Timeline\Factory */
    private $timeline_factory;

    /** @var Tuleap\PullRequest\Comment\Factory */
    private $comment_factory;

    /** @var PaginatedCommentsRepresentationsBuilder */
    private $paginated_timeline_representation_builder;

    /** @var PaginatedCommentsRepresentationsBuilder */
    private $paginated_comments_representations_builder;

    /** @var UserManager */
    private $user_manager;

    /** @var Tuleap\PullRequest\PullRequestMerger */
    private $pull_request_merger;

    /** @var Tuleap\PullRequest\PullRequestCloser */
    private $pull_request_closer;

    /** @var Tuleap\PullRequest\PullRequestCreator */
    private $pull_request_creator;

    /** @var Tuleap\PullRequest\Timeline\TimelineEventCreator */
    private $timeline_event_creator;

    /** @var EventManager */
    private $event_manager;

    /** @var BackendLogger */
    private $logger;

    /**
     * @var InlineCommentCreator
     */
    private $inline_comment_creator;

    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;
    /**
     * @var GitPullRequestReferenceRetriever
     */
    private $git_pull_request_reference_retriever;
    /**
     * @var GitPullRequestReferenceUpdater
     */
    private $git_pull_request_reference_updater;

    public function __construct()
    {
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $pull_request_dao           = new PullRequestDao();
        $reference_manager          = ReferenceManager::instance();
        $this->pull_request_factory = new PullRequestFactory($pull_request_dao, $reference_manager);

        $comment_dao           = new CommentDao();
        $this->comment_factory = new CommentFactory($comment_dao, $reference_manager);

        $inline_comment_dao     = new InlineCommentDao();
        $timeline_dao           = new TimelineDao();
        $this->timeline_factory = new TimelineFactory($comment_dao, $inline_comment_dao, $timeline_dao);

        $this->paginated_timeline_representation_builder = new PaginatedTimelineRepresentationBuilder(
            $this->timeline_factory
        );

        $this->paginated_comments_representations_builder = new PaginatedCommentsRepresentationsBuilder(
            $this->comment_factory
        );

        $this->user_manager         = UserManager::instance();
        $this->event_manager        = EventManager::instance();
        $this->pull_request_merger  = new PullRequestMerger($this->git_repository_factory);
        $this->pull_request_creator = new PullRequestCreator(
            $this->pull_request_factory,
            $pull_request_dao,
            $this->pull_request_merger,
            $this->event_manager,
            new GitPullRequestReferenceCreator(
                new GitPullRequestReferenceDAO,
                new GitPullRequestReferenceNamespaceAvailabilityChecker
            )
        );
        $this->pull_request_closer  = new PullRequestCloser($this->pull_request_factory, $this->pull_request_merger);
        $this->logger               = new BackendLogger();

        $this->timeline_event_creator = new TimelineEventCreator(new TimelineDao());

        $dao = new \Tuleap\PullRequest\InlineComment\Dao();
        $this->inline_comment_creator = new InlineCommentCreator($dao, $reference_manager);

        $this->access_control_verifier = new AccessControlVerifier(
            new FineGrainedRetriever(new FineGrainedDao()),
            new \System_Command()
        );

        $this->labels_retriever = new LabelsCurlyCoatedRetriever(
            new PaginatedCollectionsOfLabelsBuilder(),
            new PullRequestLabelDao()
        );
        $this->labels_updater   = new LabelsUpdater(new LabelDao(), new PullRequestLabelDao(), new ProjectHistoryDao());

        $this->permission_checker = new PullRequestPermissionChecker(
            $this->git_repository_factory,
            new URLVerification()
        );

        $git_pull_request_reference_dao             = new GitPullRequestReferenceDAO;
        $git_namespace_availability_checker         = new GitPullRequestReferenceNamespaceAvailabilityChecker;
        $this->git_pull_request_reference_retriever = new GitPullRequestReferenceRetriever($git_pull_request_reference_dao);
        $this->git_pull_request_reference_updater   = new GitPullRequestReferenceUpdater(
            $git_pull_request_reference_dao,
            new GitPullRequestReferenceCreator($git_pull_request_reference_dao, $git_namespace_availability_checker),
            $git_namespace_availability_checker
        );
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
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
    protected function get($id)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $user                            = $this->user_manager->getCurrentUser();
        $repository_src                  = $this->getRepository($pull_request->getRepositoryId());
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        $pr_representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            new CommitStatusRetriever(new CommitStatusDAO),
            $this->getGitoliteAccessURLGenerator()
        );

        return $pr_representation_factory->getPullRequestRepresentation(
            $pull_request_with_git_reference,
            $repository_src,
            $repository_dest,
            GitExec::buildFromRepository($repository_dest),
            $user
        );
    }

    /**
     * @url OPTIONS {id}/labels
     */
    public function optionsLabels($id)
    {
        $this->sendAllowHeadersForLabels();
    }

    /**
     * Get labels
     *
     * Get the labels that are defined for this pull request
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/labels
     *
     * @access protected
     *
     * @param int $id pull request ID
     * @param int $limit
     * @param int $offset
     *
     * @return array
     *
     * @throws 403
     * @throws 404 x Pull request does not exist
     */
    protected function getLabels($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForLabels();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();

        $collection = $this->labels_retriever->getPaginatedLabelsForPullRequest($pull_request, $limit, $offset);
        $labels_representation = array_map(
            function (Label $label) {
                $representation = new LabelRepresentation();
                $representation->build($label);

                return $representation;
            },
            $collection->getLabels()
        );

        $this->sendAllowHeadersForLabels();
        Header::sendPaginationHeaders($limit, $offset, $collection->getTotalSize(), self::MAX_LIMIT);

        return array(
            'labels' => $labels_representation
        );
    }

    /**
     * Update labels
     *
     * <p>Update the labels of the pull request. You can add or remove labels.</p>
     *
     * <p>Example of payload:</p>
     *
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"add": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 1 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 2 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 3 }<br>
     * &nbsp;&nbsp;],<br>
     * &nbsp;&nbsp;"remove": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 4 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 5 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 6 }<br>
     * &nbsp;&nbsp;]<br>
     * }<br>
     * </pre>
     *
     * <p>This will add labels with ids 1, 2, and 3; and will remove labels with ids 4, 5, and 6.</p>
     *
     * <p>You can also create labels, they will be added to the list of project labels. Example:</p>
     *
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"add": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 1 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 2 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "label": "Emergency Fix" }<br>
     * &nbsp;&nbsp;]<br>
     * }<br>
     * </pre>
     *
     * <p>This will create "Emergency Fix" label (if it does not already exist, else it uses the existing one),
     * and add it to the current pull request. Please note that you must use the id to remove labels from the
     * pull request.</p>
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/labels
     *
     * @access protected
     *
     * @param int $id pull request ID
     * @param LabelsPATCHRepresentation $body
     *
     * @throws 400
     * @throws 403
     * @throws 404 x Pull request does not exist
     */
    protected function patchLabels($id, LabelsPATCHRepresentation $body)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForLabels();

        $pull_request_with_git_reference = $this->getWritablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        try {
            $this->labels_updater->update($repository_dest->getProjectId(), $pull_request, $body);
        } catch (UnknownLabelException $exception) {
            throw new RestException(400, "Label is unknown in the project");
        } catch (UnableToAddAndRemoveSameLabelException $exception) {
            throw new RestException(400, "Unable to add and remove the same label");
        } catch (UnableToAddEmptyLabelException $exception) {
            throw new RestException(400, "Unable to add an empty label");
        } catch (\Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }
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
    protected function getFiles($id)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepositoryId());
        $executor                        = $this->getExecutor($git_repository_destination);

        $file_representation_factory = new PullRequestFileRepresentationFactory($executor);

        try {
            $modified_files = $file_representation_factory->getModifiedFilesRepresentations($pull_request);
        } catch (UnknownReferenceException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        return $modified_files;
    }

    /**
     * Get the diff of a given file in a pull request
     *
     * Get the diff of a given file between the source branch and the dest branch for a pull request.<br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/file_diff
     *
     * @access protected
     *
     * @param  int $id pull request ID
     * @param  string $path File path {@from query}
     *
     * @return PullRequestFileUniDiffRepresentation {@type Tuleap\PullRequest\REST\v1\PullRequestFileUniDiffRepresentation}
     *
     * @throws 403
     * @throws 404 x Pull request does not exist
     * @throws 404 x The file does not exist
     */
    protected function getFileDiff($id, $path)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());

        $executor_repo_destination = $this->getExecutor($git_repository_destination);
        $dest_content              = $this->getDestinationContent($pull_request, $executor_repo_destination, $path);
        $src_content               = $this->getSourceContent($pull_request, $executor_repo_destination, $path);

        if ($src_content === null && $dest_content === null) {
            throw new RestException(404, 'The file does not exist');
        }

        list($mime_type, $charset) = MimeDetector::getMimeInfo($path, $dest_content, $src_content);

        if ($charset === "binary") {
            $diff = new FileUniDiff();
            $inline_comments = array();
        } else {
            $unidiff_builder = new FileUniDiffBuilder();
            $diff            = $unidiff_builder->buildFileUniDiffFromCommonAncestor(
                $executor_repo_destination,
                $path,
                $pull_request->getSha1Dest(),
                $pull_request->getSha1Src()
            );

            $inline_comment_builder = new PullRequestInlineCommentRepresentationBuilder(
                new \Tuleap\PullRequest\InlineComment\Dao(),
                $this->user_manager
            );
            $git_repository_source = $this->getRepository($pull_request->getRepositoryId());
            $inline_comments       = $inline_comment_builder->getForFile($pull_request, $path, $git_repository_source->getProjectId());
        }

        return PullRequestFileUniDiffRepresentation::build($diff, $inline_comments, $mime_type, $charset);
    }

    /**
     * Post a new inline comment
     *
     * Post a new inline comment for a given pull request file<br>
     * Format: { "content": "My new comment" , "unidiff_offset": 1, "file_path": "dir/myfile.txt"}
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url POST {id}/inline-comments
     *
     * @access protected
     *
     * @param int $id Pull request id
     * @param PullRequestInlineCommentPOSTRepresentation $comment_data Comment {@from body} {@type Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation}
     *
     * @status 201
     */
    protected function postInline($id, PullRequestInlineCommentPOSTRepresentation $comment_data)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForInlineComments();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_source           = $this->getRepository($pull_request->getRepositoryId());
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());
        $user                            = $this->user_manager->getCurrentUser();

        $executor     = $this->getExecutor($git_repository_destination);
        $dest_content = $this->getDestinationContent($pull_request, $executor, $comment_data->file_path);
        $src_content  = $this->getSourceContent($pull_request, $executor, $comment_data->file_path);

        if ($src_content === null && $dest_content === null) {
            throw new RestException(404, 'The file does not exist');
        }

        $post_date = time();
        $this->inline_comment_creator->insert($pull_request, $user, $comment_data, $post_date, $git_repository_source->getProjectId());

        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($this->user_manager->getUserById($user->getId()));

        return new PullRequestInlineCommentRepresentation(
            $comment_data->unidiff_offset,
            $user_representation,
            $post_date,
            $comment_data->content,
            $git_repository_source->getProjectId()
        );
    }


    private function getSourceContent(PullRequest $pull_request, GitExec $executor, $path)
    {
        try {
            $src_content  = $executor->getFileContent($pull_request->getSha1Src(), $path);
        } catch (Git_Command_Exception $exception) {
            $src_content = null;
        }

        return $src_content;
    }

    private function getDestinationContent(PullRequest $pull_request, GitExec $executor, $path)
    {
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
     * &nbsp;&nbsp;"repository_dest_id": 3,<br/>
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
    protected function post(PullRequestPOSTRepresentation $content)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                = $this->user_manager->getCurrentUser();

        $repository_id       = $content->repository_id;
        $repository_src      = $this->getRepository($repository_id);
        $branch_src          = $content->branch_src;

        $repository_dest_id  = $content->repository_dest_id;
        $repository_dest     = $this->getRepository($repository_dest_id);
        $branch_dest         = $content->branch_dest;

        $this->checkUserCanReadRepository($user, $repository_src);

        try {
            $generated_pull_request = $this->pull_request_creator->generatePullRequest(
                $repository_src,
                $branch_src,
                $repository_dest,
                $branch_dest,
                $user
            );
        } catch (UnknownBranchNameException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestCannotBeCreatedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestAlreadyExistsException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestRepositoryMigratedOnGerritException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestAnonymousUserException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        if (! $generated_pull_request) {
            throw new RestException(500);
        }

        $pull_request_reference = new PullRequestReference();
        $pull_request_reference->build($generated_pull_request);

        $this->sendLocationHeader($pull_request_reference->uri);

        return $pull_request_reference;
    }

    /**
     * Partial update of a pull request
     *
     * Merge or abandon a pull request.
     * <br/>
     * -- OR --
     * <br/>
     * Update title and description of pull request.
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
     * <br/>
     *
     * Here is an example of a valid PATCH content to update a pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"title": "new title",<br/>
     * &nbsp;&nbsp;"description": "new description"<br/>
     * }<br/>
     * </pre>
     * <br/>
     *
     * @url PATCH {id}
     *
     * @access protected
     *
     * @param  int $id pull request ID
     * @param  PullRequestPATCHRepresentation $body new pull request status {@from body}
     * @return array {@type Tuleap\PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws 400
     * @throws 403
     * @throws 404 x Pull request does not exist
     * @throws 500 x Error while abandoning the pull request
     * @throws 500 x Error while merging the pull request
     */
    protected function patch($id, PullRequestPATCHRepresentation $body)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getWritablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $repository_src                  = $this->getRepository($pull_request->getRepositoryId());
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        $status = $body->status;
        if ($status !== null) {
            $this->patchStatus($user, $pull_request, $status);
        } else {
            $this->patchInfo(
                $user,
                $pull_request,
                $repository_src->getProjectId(),
                $body
            );
        }
        $updated_pull_request = $this->pull_request_factory->getPullRequestById($id);

        $pr_representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            new CommitStatusRetriever(new CommitStatusDAO),
            $this->getGitoliteAccessURLGenerator()
        );

        return $pr_representation_factory->getPullRequestRepresentation(
            new PullRequestWithGitReference($updated_pull_request, $pull_request_with_git_reference->getGitReference()),
            $repository_src,
            $repository_dest,
            GitExec::buildFromRepository($repository_dest),
            $user
        );
    }

    private function patchStatus(PFUser $user, PullRequest $pull_request, $status)
    {
        switch ($status) {
            case PullRequestRepresentation::STATUS_ABANDON:
                try {
                    $this->abandon($pull_request);
                    $this->timeline_event_creator->storeAbandonEvent($pull_request, $user);
                } catch (PullRequestCannotBeAbandoned $exception) {
                    throw new RestException(400, $exception->getMessage());
                }
                break;
            case PullRequestRepresentation::STATUS_MERGE:
                $git_repository_dest = $this->getRepository($pull_request->getRepoDestId());

                try {
                    $this->pull_request_closer->doMerge($git_repository_dest, $pull_request, $user);
                    $this->timeline_event_creator->storeMergeEvent($pull_request, $user);
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

    private function abandon(PullRequest $pull_request)
    {
        $this->pull_request_closer->abandon($pull_request);
    }

    private function patchInfo(
        PFUser $user,
        PullRequest $pull_request,
        $project_id,
        $body
    ) {
        $this->pull_request_factory->updateTitleAndDescription(
            $user,
            $pull_request,
            $project_id,
            $body->title,
            $body->description
        );
    }

    /**
     * @url OPTIONS {id}/timeline
     */
    public function optionsTimeline($id)
    {
        return $this->sendAllowHeadersForTimeline();
    }

    /**
     * Get pull request's timeline
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/timeline
     *
     * @access protected
     *
     * @param int    $id     Pull request id
     * @param int    $limit  Number of fetched comments {@from path}
     * @param int    $offset Position of the first comment to fetch {@from path}
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\TimelineRepresentation}
     *
     * @throws 404
     * @throws 406
     */
    protected function getTimeline($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $this->checkLimit($limit);
        $this->sendAllowHeadersForTimeline();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        $paginated_timeline_representation = $this->paginated_timeline_representation_builder->getPaginatedTimelineRepresentation(
            $id,
            $project_id,
            $limit,
            $offset
        );

        Header::sendPaginationHeaders($limit, $offset, $paginated_timeline_representation->total_size, self::MAX_LIMIT);

        return $paginated_timeline_representation;
    }

    /**
     * @url OPTIONS {id}/comments
     */
    public function optionsComments($id)
    {
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
    protected function getComments($id, $limit = 10, $offset = 0, $order = 'asc')
    {
        $this->checkAccess();
        $this->checkLimit($limit);
        $this->sendAllowHeadersForComments();

        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        $paginated_comments_representations = $this->paginated_comments_representations_builder->getPaginatedCommentsRepresentations(
            $id,
            $project_id,
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
    protected function postComments($id, CommentPOSTRepresentation $comment_data)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForComments();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        $current_time   = time();
        $comment        = new Comment(0, $id, $user->getId(), $current_time, $comment_data->content);
        $new_comment_id = $this->comment_factory->save($comment, $user, $project_id);

        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($user);

        $comment_representation = new CommentRepresentation();
        $comment_representation->build($new_comment_id, $project_id, $user_representation, $comment->getPostDate(), $comment->getContent());

        return $comment_representation;
    }

    private function checkLimit($limit)
    {
        if ($limit > self::MAX_LIMIT) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    /**
     * @param $id
     * @return PullRequestWithGitReference
     */
    private function getWritablePullRequestWithGitReference($id)
    {
        $pull_request_with_git_reference = $this->getReadablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();

        $current_user    = $this->user_manager->getCurrentUser();
        $repository_dest = $this->getRepository($pull_request->getRepoDestId());
        $this->checkUserCanWrite($current_user, $repository_dest, $pull_request->getBranchDest());

        return $pull_request_with_git_reference;
    }

    /**
     * @param $id
     * @return PullRequestWithGitReference
     */
    private function getReadablePullRequestWithGitReference($id)
    {
        try {
            $pull_request = $this->pull_request_factory->getPullRequestById($id);
            $current_user = $this->user_manager->getCurrentUser();
            $this->permission_checker->checkPullRequestIsReadableByUser($pull_request, $current_user);

            $git_reference = $this->git_pull_request_reference_retriever->getGitReferenceFromPullRequest($pull_request);
        } catch (PullRequestNotFoundException $exception) {
            throw new RestException(404);
        } catch (\GitRepoNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (UserCannotReadGitRepositoryException $exception) {
            throw new RestException(403, 'User is not able to READ the git repository');
        } catch (GitPullRequestReferenceNotFoundException $exception) {
            throw new RestException(404);
        }

        if ($git_reference->isGitReferenceBroken()) {
            throw new RestException(
                410,
                dgettext('tuleap-pullrequest', 'The pull request is not accessible anymore')
            );
        }

        $this->updateGitReferenceIfNeeded($pull_request, $git_reference);

        return new PullRequestWithGitReference($pull_request, $git_reference);
    }

    private function updateGitReferenceIfNeeded(PullRequest $pull_request, GitPullRequestReference $git_reference)
    {
        if (! $git_reference->isGitReferenceNeedToBeCreatedInRepository()) {
            return;
        }
        $repository_source      = $this->getRepository($pull_request->getRepositoryId());
        $repository_destination = $this->getRepository($pull_request->getRepoDestId());
        $this->git_pull_request_reference_updater->updatePullRequestReference(
            $pull_request,
            GitExec::buildFromRepository($repository_source),
            GitExec::buildFromRepository($repository_destination),
            $repository_destination
        );
    }

    private function getRepository($repository_id)
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new RestException(404, "Git repository not found");
        }

        return $repository;
    }

    private function checkUserCanReadRepository(PFUser $user, GitRepository $repository)
    {
        ProjectAuthorization::userCanAccessProject($user, $repository->getProject(), new URLVerification());

        if (! $repository->userCanRead($user)) {
            throw new RestException(403, 'User is not able to READ the git repository');
        }
    }

    private function checkUserCanWrite(PFUser $user, GitRepository $repository, $reference)
    {
        ProjectAuthorization::userCanAccessProject($user, $repository->getProject(), new URLVerification());

        if (! $this->access_control_verifier->canWrite($user, $repository, $reference)) {
            throw new RestException(403, 'User is not able to WRITE the git repository');
        }
    }

    private function sendLocationHeader($uri)
    {
        $uri_with_api_version = '/api/v1/' . $uri;

        Header::Location($uri_with_api_version);
    }

    private function sendAllowHeadersForPullRequests()
    {
        Header::allowOptionsGetPostPatch();
    }

    private function sendAllowHeadersForTimeline()
    {
        HEADER::allowOptionsGet();
    }

    private function sendAllowHeadersForLabels()
    {
        HEADER::allowOptionsGetPatch();
    }

    private function sendAllowHeadersForComments()
    {
        HEADER::allowOptionsGetPost();
    }

    private function sendAllowHeadersForInlineComments()
    {
        HEADER::allowOptionsGetPost();
    }

    /**
     * @return GitExec
     */
    private function getExecutor(GitRepository $git_repository)
    {
        return new GitExec($git_repository->getFullPath(), $git_repository->getFullPath());
    }

    /**
     * @return GitoliteAccessURLGenerator
     */
    private function getGitoliteAccessURLGenerator()
    {
        $git_plugin = \PluginFactory::instance()->getPluginByName('git');
        return new GitoliteAccessURLGenerator($git_plugin->getPluginInfo());
    }
}
