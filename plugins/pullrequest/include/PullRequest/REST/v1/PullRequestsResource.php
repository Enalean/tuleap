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

use BackendLogger;
use EventManager;
use Git_GitRepositoryUrlManager;
use GitDao;
use GitPlugin;
use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use PFUser;
use PluginFactory;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\GitPHP\Pack;
use Tuleap\Git\GitPHP\ProjectProvider;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\REST\v1\GitCommitRepresentationBuilder;
use Tuleap\Label\Label;
use Tuleap\Label\PaginatedCollectionsOfLabelsBuilder;
use Tuleap\Label\REST\LabelRepresentation;
use Tuleap\Label\REST\LabelsPATCHRepresentation;
use Tuleap\Label\REST\LabelsUpdater;
use Tuleap\Label\REST\UnableToAddAndRemoveSameLabelException;
use Tuleap\Label\REST\UnableToAddEmptyLabelException;
use Tuleap\Label\UnknownLabelException;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\Option\Option;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\REST\UserRESTReferenceRetriever;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\Comment\Factory as CommentFactory;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Events\PullRequestDiffRepresentationBuild;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestTargetException;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitExecFactory;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceCreator;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceDAO;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNamespaceAvailabilityChecker;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use Tuleap\PullRequest\Label\LabelsCurlyCoatedRetriever;
use Tuleap\PullRequest\Label\PullRequestLabelDao;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;
use Tuleap\PullRequest\Notification\PullRequestNotificationSupport;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\REST\v1\AccessiblePullRequestRESTRetriever;
use Tuleap\PullRequest\PullRequest\REST\v1\PullRequestWithGitReferenceRetriever;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\PullRequest\PullRequestCreator;
use Tuleap\PullRequest\PullRequestCreatorChecker;
use Tuleap\PullRequest\PullRequestMerger;
use Tuleap\PullRequest\PullRequestReopener;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\PullRequestUpdater;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\REST\v1\Comment\CommentRepresentation;
use Tuleap\PullRequest\REST\v1\Comment\CommentRepresentationBuilder;
use Tuleap\PullRequest\REST\v1\Comment\ParentIdValidatorForComment;
use Tuleap\PullRequest\REST\v1\Comment\ParentIdValidatorForInlineComment;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\REST\v1\Info\PullRequestInfoUpdater;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentation;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentationsBuilder;
use Tuleap\PullRequest\REST\v1\InlineComment\POSTHandler;
use Tuleap\PullRequest\REST\v1\InlineComment\SingleRepresentationBuilder;
use Tuleap\PullRequest\REST\v1\Permissions\PullRequestIsMergeableChecker;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewerRepresentationInformationExtractor;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersPUTRepresentation;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersRepresentation;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeDAO;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\Reviewer\ReviewersCannotBeUpdatedOnClosedPullRequestException;
use Tuleap\PullRequest\Reviewer\ReviewerUpdater;
use Tuleap\PullRequest\Reviewer\UserCannotBeAddedAsReviewerException;
use Tuleap\PullRequest\Timeline\Dao as TimelineDao;
use Tuleap\PullRequest\Timeline\Factory as TimelineFactory;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\User\REST\MinimalUserRepresentation;
use URLVerification;
use UserManager;
use function Psl\Type\int;

class PullRequestsResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    private PullRequestPermissionChecker $permission_checker;
    private LabelsUpdater $labels_updater;
    private LabelsCurlyCoatedRetriever $labels_retriever;
    private GitRepositoryFactory $git_repository_factory;
    private PullRequestFactory $pull_request_factory;
    private CommentFactory $comment_factory;
    private UserManager $user_manager;
    private PullRequestCloser $pull_request_closer;
    private PullRequestCreator $pull_request_creator;
    private EventManager $event_manager;
    private LoggerInterface $logger;
    private AccessControlVerifier $access_control_verifier;
    private GitPlugin $git_plugin;
    private GitCommitRepresentationBuilder $commit_representation_builder;
    private CommitStatusRetriever $status_retriever;
    private readonly PullRequestDao $pull_request_dao;

    public function __construct()
    {
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $this->pull_request_dao     = new PullRequestDao();
        $reference_manager          = ReferenceManager::instance();
        $this->pull_request_factory = new PullRequestFactory($this->pull_request_dao, $reference_manager);

        $this->logger = BackendLogger::getDefaultLogger();

        $event_dispatcher = PullRequestNotificationSupport::buildDispatcher($this->logger);

        $comment_dao           = new CommentDao();
        $this->comment_factory = new CommentFactory($comment_dao, $reference_manager, $event_dispatcher);

        $this->user_manager = UserManager::instance();

        $this->event_manager        = EventManager::instance();
        $pull_request_merger        = new PullRequestMerger(
            new MergeSettingRetriever(new MergeSettingDAO())
        );
        $this->pull_request_creator = new PullRequestCreator(
            new PullRequestCreatorChecker($this->pull_request_dao),
            $this->pull_request_factory,
            $pull_request_merger,
            $this->event_manager,
            new GitPullRequestReferenceCreator(
                new GitPullRequestReferenceDAO(),
                new GitPullRequestReferenceNamespaceAvailabilityChecker()
            )
        );
        $this->pull_request_closer  = new PullRequestCloser(
            $this->pull_request_dao,
            $pull_request_merger,
            new TimelineEventCreator(new TimelineDao()),
            $event_dispatcher
        );

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
            new \Tuleap\Project\ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                $this->event_manager
            ),
            $this->access_control_verifier
        );

        $git_plugin = PluginFactory::instance()->getPluginByName('git');
        assert($git_plugin instanceof \GitPlugin);
        $this->git_plugin = $git_plugin;
        $url_manager      = new Git_GitRepositoryUrlManager($this->git_plugin);

        $this->status_retriever              = new CommitStatusRetriever(new CommitStatusDAO());
        $metadata_retriever                  = new CommitMetadataRetriever($this->status_retriever, $this->user_manager);
        $this->commit_representation_builder = new GitCommitRepresentationBuilder(
            $metadata_retriever,
            $url_manager
        );
    }

    /**
     * @url OPTIONS
     */
    public function options(): void
    {
        $this->sendAllowHeadersForPullRequests();
    }

    /**
     * Get pull request
     *
     * Retrieve a given pull request. <br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * @url GET {id}
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @return PullRequestRepresentation {@type Tuleap\PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function get($id)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $user                            = $this->user_manager->getCurrentUser();
        $repository_src                  = $this->getRepository($pull_request->getRepositoryId());
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $repository_src->getProject()
        );

        $purifier            = \Codendi_HTMLPurifier::instance();
        $content_interpretor = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );

        $pr_representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            $this->status_retriever,
            $this->getGitoliteAccessURLGenerator(),
            new PullRequestStatusInfoRepresentationBuilder(new TimelineDao(), new TimelineDao(), UserManager::instance()),
            $purifier,
            $content_interpretor
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
     * Get pull request commits
     *
     * Retrieve all commits of a given pull request. <br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * @url    GET {id}/commits
     *
     * @access hybrid
     *
     * @param int $id     pull request ID
     * @param int $limit  Number of fetched comments {@from path} {@min 0}{@max 50}
     * @param int $offset Position of the first comment to fetch {@from path} {@min 0}
     *
     * @return array {@type \Tuleap\Git\REST\v1\GitCommitRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     * @throws RestException 410
     * @throws RestException 500
     */
    public function getCommits($id, $limit = 50, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForCommits();

        $user                             = $this->user_manager->getCurrentUser();
        $pull_requests_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);

        $pull_request   = $pull_requests_with_git_reference->getPullRequest();
        $git_repository = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $git_repository->getProject()
        );

        $provider = new ProjectProvider($git_repository);

        $commit_factory = new PullRequestsCommitRepresentationFactory(
            $this->getExecutor($git_repository),
            $provider->GetProject(),
            $this->git_repository_factory,
            $this->commit_representation_builder
        );

        $commit_representation = $commit_factory->getPullRequestCommits(
            $pull_requests_with_git_reference->getPullRequest(),
            $limit,
            $offset
        );

        Header::sendPaginationHeaders($limit, $offset, $commit_representation->getSize(), self::MAX_LIMIT);

        return $commit_representation->getCommitsCollection();
    }

    /**
     * @url OPTIONS {id}/commits
     */
    public function optionsCommits($id)
    {
        $this->sendAllowHeadersForCommits();
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
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function getLabels($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForLabels();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $dest_repository                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $dest_repository->getProject()
        );

        $collection            = $this->labels_retriever->getPaginatedLabelsForPullRequest($pull_request, $limit, $offset);
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

        return [
            'labels' => $labels_representation,
        ];
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
     * @url PATCH {id}/labels
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function patchLabels($id, LabelsPATCHRepresentation $body)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForLabels();

        $pull_request_with_git_reference = $this->getWritablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository_dest->getProject()
        );

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
     * @url GET {id}/files
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @return array {@type PullRequest\REST\v1\PullRequestFileRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function getFiles($id)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());
        $executor                        = $this->getExecutor($git_repository_destination);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $git_repository_destination->getProject()
        );

        $file_representation_factory = new PullRequestFileRepresentationFactory($executor);

        $modified_files = $file_representation_factory->getModifiedFilesRepresentations($pull_request);

        return $modified_files;
    }

    /**
     * Get the diff of a given file in a pull request
     *
     * Get the diff of a given file between the source branch and the dest branch for a pull request.<br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
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
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     * @throws RestException 404 x The file does not exist
     */
    protected function getFileDiff($id, $path)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $git_repository_destination->getProject()
        );

        $git_project = (new ProjectProvider($git_repository_destination))->GetProject();
        $commit_src  = $git_project->GetCommit($pull_request->getSha1Src());
        $commit_dest = $git_project->GetCommit($pull_request->getSha1Dest());

        $object_reference_src  = $commit_src->PathToHash($path);
        $object_reference_dest = $commit_dest->PathToHash($path);

        if ($object_reference_src === '' && $object_reference_dest === '') {
            throw new RestException(404, 'The file does not exist');
        }

        $object_src  = $git_project->GetObject($object_reference_src, $object_type_src) ?: "";
        $object_dest = $git_project->GetObject($object_reference_dest, $object_type_dest) ?: "";

        $mime_type = 'text/plain';
        $charset   = 'utf-8';
        if ($object_type_src === Pack::OBJ_BLOB || $object_type_dest === Pack::OBJ_BLOB) {
            [$mime_type, $charset] = MimeDetector::getMimeInfo($path, $object_dest, $object_src);
        }

        $event = new PullRequestDiffRepresentationBuild($object_dest, $object_src);
        $this->event_manager->processEvent($event);

        $special_format = $event->getSpecialFormat();

        if ($charset === "binary" || $special_format !== '') {
            $diff            = new FileUniDiff();
            $inline_comments = [];
        } else {
            $executor_repo_destination = $this->getExecutor($git_repository_destination);
            $unidiff_builder           = new FileUniDiffBuilder();
            $diff                      = $unidiff_builder->buildFileUniDiffFromCommonAncestor(
                $executor_repo_destination,
                $path,
                $pull_request->getSha1Dest(),
                $pull_request->getSha1Src()
            );

            $purifier            = \Codendi_HTMLPurifier::instance();
            $content_interpretor = CommonMarkInterpreter::build(
                $purifier,
                new EnhancedCodeBlockExtension(new CodeBlockFeatures())
            );

            $inline_comment_builder = new InlineCommentRepresentationsBuilder(
                new \Tuleap\PullRequest\InlineComment\Dao(),
                $this->user_manager,
                new SingleRepresentationBuilder($purifier, $content_interpretor)
            );
            $git_repository_source  = $this->getRepository($pull_request->getRepositoryId());
            $inline_comments        = $inline_comment_builder->getForFile($pull_request, $path, $git_repository_source->getProjectId());
        }

        return PullRequestFileUniDiffRepresentation::build($diff, $inline_comments, $mime_type, $charset, $special_format);
    }

    /**
     * @url OPTIONS {id}/inline-comments
     * @param int $id Pull request ID
     */
    public function optionsInlineComments(int $id): void
    {
        $this->sendAllowHeadersForInlineComments();
    }

    /**
     * Post a new inline comment
     *
     * Post a new inline comment for a given pull request file and a position (left | right)<br>
     * Format: { "content": "My new comment" , "unidiff_offset": 1, "file_path": "dir/myfile.txt" , position: "left" }
     *
     * @url POST {id}/inline-comments
     *
     * @access protected
     *
     * @param int $id Pull request id
     * @param PullRequestInlineCommentPOSTRepresentation $comment_data Comment {@from body}
     *
     * @status 201
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 410
     */
    protected function postInline(int $id, PullRequestInlineCommentPOSTRepresentation $comment_data): InlineCommentRepresentation
    {
        $this->checkAccess();
        $this->sendAllowHeadersForInlineComments();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $git_repository_destination->getProject()
        );

        $git_project = (new ProjectProvider($git_repository_destination))->GetProject();
        $commit_src  = $git_project->GetCommit($pull_request->getSha1Src());
        $commit_dest = $git_project->GetCommit($pull_request->getSha1Dest());

        $object_reference_src  = $commit_src->PathToHash($comment_data->file_path);
        $object_reference_dest = $commit_dest->PathToHash($comment_data->file_path);

        if ($object_reference_src === '' && $object_reference_dest === '') {
            throw new RestException(404, 'The file does not exist');
        }

        if (! in_array($comment_data->position, ['left', 'right'])) {
            throw new RestException(400, 'Please provide a valid position, either left or right');
        }

        $parent_id_validator = new ParentIdValidatorForInlineComment(new InlineCommentRetriever(new InlineCommentDao()));
        $parent_id_validator->checkParentValidity((int) $comment_data->parent_id, $id);

        $post_date = new \DateTimeImmutable();

        $dao                 = new \Tuleap\PullRequest\InlineComment\Dao();
        $color_retriever     = new ThreadCommentColorRetriever(new ThreadCommentDao(), $dao);
        $color_assigner      = new ThreadCommentColorAssigner($dao, $dao);
        $comment_creator     = new InlineCommentCreator(
            $dao,
            ReferenceManager::instance(),
            PullRequestNotificationSupport::buildDispatcher($this->logger),
            $color_retriever,
            $color_assigner
        );
        $purifier            = \Codendi_HTMLPurifier::instance();
        $content_interpreter = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );

        $handler = new POSTHandler(
            $this->git_repository_factory,
            $comment_creator,
            new SingleRepresentationBuilder($purifier, $content_interpreter)
        );
        return $handler->handle($comment_data, $user, $post_date, $pull_request)
            ->match(
                static fn(InlineCommentRepresentation $representation) => $representation,
                FaultMapper::mapToRestException(...)
            );
    }

    /**
     * Create PullRequest
     *
     * Create a new pullrequest.<br/>
     *
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
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @status 201
     */
    protected function post(PullRequestPOSTRepresentation $content)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user = $this->user_manager->getCurrentUser();

        $repository_id  = $content->repository_id;
        $repository_src = $this->getRepository($repository_id);
        $branch_src     = $content->branch_src;

        $repository_dest_id = $content->repository_dest_id;
        $repository_dest    = $this->getRepository($repository_dest_id);
        $branch_dest        = $content->branch_dest;

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository_dest->getProject()
        );

        $this->checkUserCanReadRepository($user, $repository_src);

        try {
            $generated_pull_request = $this->pull_request_creator->generatePullRequest(
                $repository_src,
                $branch_src,
                $repository_dest,
                $branch_dest,
                $user,
                TimelineComment::FORMAT_MARKDOWN,
            );
        } catch (UnknownBranchNameException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestAlreadyExistsException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestRepositoryMigratedOnGerritException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestAnonymousUserException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestCannotBeCreatedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestTargetException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $pull_request_reference = PullRequestReference::fromPullRequest($generated_pull_request);

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
     * Update title and/or description of pull request.
     *
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
     * A pull request that has been abandoned can be reopen.<br/>
     * Here is an example of a valid PATCH content to reopen an abandoned pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"status": "review"<br/>
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
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     * @throws RestException 500 x Error while abandoning the pull request
     * @throws RestException 500 x Error while merging the pull request
     */
    protected function patch($id, PullRequestPATCHRepresentation $body)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $repository_src                  = $this->getRepository($pull_request->getRepositoryId());
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository_dest->getProject()
        );

        $status = $body->status;
        if ($status !== null) {
            $this->patchStatus($user, $pull_request, $status);
        } else {
            $patch_info_updater = new PullRequestInfoUpdater($this->pull_request_factory, $this->getPullRequestIsMergeableChecker());
            $patch_info_updater->patchInfo(
                $user,
                $pull_request,
                (int) $repository_src->getProjectId(),
                $body
            );
        }
        $updated_pull_request = $this->pull_request_factory->getPullRequestById($id);

        $purifier                  = \Codendi_HTMLPurifier::instance();
        $content_interpretor       = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );
        $pr_representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            new CommitStatusRetriever(new CommitStatusDAO()),
            $this->getGitoliteAccessURLGenerator(),
            new PullRequestStatusInfoRepresentationBuilder(new TimelineDao(), new TimelineDao(), UserManager::instance()),
            $purifier,
            $content_interpretor
        );

        return $pr_representation_factory->getPullRequestRepresentation(
            new PullRequestWithGitReference($updated_pull_request, $pull_request_with_git_reference->getGitReference()),
            $repository_src,
            $repository_dest,
            GitExec::buildFromRepository($repository_dest),
            $user
        );
    }

    /**
     * @throws RestException
     */
    private function patchStatus(PFUser $user, PullRequest $pull_request, $status)
    {
        $status_patcher = new StatusPatcher(
            $this->git_repository_factory,
            $this->access_control_verifier,
            $this->permission_checker,
            $this->pull_request_closer,
            new PullRequestReopener(
                new PullRequestDao(),
                $this->git_repository_factory,
                new GitExecFactory(),
                new PullRequestUpdater(
                    $this->pull_request_factory,
                    new PullRequestMerger(
                        new MergeSettingRetriever(new MergeSettingDAO())
                    ),
                    new InlineCommentDao(),
                    new InlineCommentUpdater(),
                    new FileUniDiffBuilder(),
                    new TimelineEventCreator(new TimelineDao()),
                    $this->git_repository_factory,
                    new \Tuleap\PullRequest\GitExecFactory(),
                    new GitPullRequestReferenceUpdater(
                        new GitPullRequestReferenceDAO(),
                        new GitPullRequestReferenceNamespaceAvailabilityChecker()
                    ),
                    PullRequestNotificationSupport::buildDispatcher(\pullrequestPlugin::getLogger())
                ),
                new TimelineEventCreator(new TimelineDao()),
            ),
            new URLVerification(),
            $this->logger
        );

        $status_patcher->patchStatus($user, $pull_request, $status);
    }

    /**
     * @url OPTIONS {id}/timeline
     * @param int $id Pull request ID
     */
    public function optionsTimeline(int $id): void
    {
        $this->sendAllowHeadersForTimeline();
    }

    /**
     * Get pull request's timeline
     *
     * @url GET {id}/timeline
     *
     * @access protected
     *
     * @param int    $id     Pull request id
     * @param int    $limit  Number of fetched comments {@from path} {@min 0} {@max 50}
     * @param int    $offset Position of the first comment to fetch {@from path} {@min 0}
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\TimelineRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function getTimeline($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForTimeline();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $git_repository->getProject()
        );

        $comment_dao        = new CommentDao();
        $inline_comment_dao = new InlineCommentDao();
        $timeline_dao       = new TimelineDao();
        $timeline_factory   = new TimelineFactory(
            $comment_dao,
            $inline_comment_dao,
            $timeline_dao,
            new ReviewerChangeRetriever(
                new ReviewerChangeDAO(),
                $this->pull_request_factory,
                $this->user_manager,
            )
        );

        $purifier            = \Codendi_HTMLPurifier::instance();
        $content_interpreter = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );


        $paginated_timeline_representation_builder = new PaginatedTimelineRepresentationBuilder(
            $timeline_factory,
            UserManager::instance(),
            $purifier,
            $content_interpreter,
            new CommentRepresentationBuilder($purifier, $content_interpreter)
        );

        $paginated_timeline_representation = $paginated_timeline_representation_builder->getPaginatedTimelineRepresentation(
            $pull_request,
            $project_id,
            $limit,
            $offset
        );

        Header::sendPaginationHeaders($limit, $offset, $paginated_timeline_representation->total_size, self::MAX_LIMIT);

        return $paginated_timeline_representation;
    }

    /**
     * @url OPTIONS {id}/comments
     * @param int $id Pull request ID
     */
    public function optionsComments(int $id): void
    {
        $this->sendAllowHeadersForComments();
    }

    /**
     * Get pull request's comments
     *
     * @url GET {id}/comments
     *
     * @access protected
     *
     * @param int    $id     Pull request id
     * @param int    $limit  Number of fetched comments {@from path} {@min 0} {@max 50}
     * @param int    $offset Position of the first comment to fetch {@from path} {@min 0}
     * @param string $order  In which order comments are fetched. Default is asc. {@from path}{@choice asc,desc}
     *
     * @return array {@type \Tuleap\PullRequest\REST\v1\Comment\CommentRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function getComments($id, $limit = 10, $offset = 0, $order = 'asc')
    {
        $this->checkAccess();
        $this->sendAllowHeadersForComments();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $git_repository->getProject()
        );
        $purifier            = \Codendi_HTMLPurifier::instance();
        $content_interpreter = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );

        $paginated_comments_representations_builder = new PaginatedCommentsRepresentationsBuilder(
            $this->comment_factory,
            $this->user_manager,
            new CommentRepresentationBuilder($purifier, $content_interpreter)
        );

        $paginated_comments_representations = $paginated_comments_representations_builder->getPaginatedCommentsRepresentations(
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
     * @url POST {id}/comments
     *
     * @access protected
     *
     * @param int $id Pull request id
     * @param CommentPOSTRepresentation $comment_data Comment {@from body}
     *
     * @status 201
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 410
     */
    protected function postComments(int $id, CommentPOSTRepresentation $comment_data): CommentRepresentation
    {
        $this->checkAccess();
        $this->sendAllowHeadersForComments();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $source_repository               = $this->getRepository($pull_request->getRepositoryId());
        $source_project_id               = $source_repository->getProjectId();

        $dao                 = new CommentDao();
        $comment_retriever   = new CommentRetriever($dao);
        $color_retriever     = new ThreadCommentColorRetriever(new ThreadCommentDao(), $dao);
        $color_assigner      = new ThreadCommentColorAssigner($dao, $dao);
        $parent_id_validator = new ParentIdValidatorForComment($comment_retriever);
        $current_time        = time();
        $format              = $comment_data->format;
        if (! $format) {
            $format = TimelineComment::FORMAT_MARKDOWN;
        }

        $color = $color_retriever->retrieveColor($id, (int) $comment_data->parent_id);
        $color_assigner->assignColor((int) $comment_data->parent_id, $color);
        $comment = new Comment(0, $id, (int) $user->getId(), $current_time, $comment_data->content, (int) $comment_data->parent_id, $color, $format, Option::nothing(int()));

        $parent_id_validator->checkParentValidity((int) $comment_data->parent_id, $id);
        $new_comment_id = $this->comment_factory->save($comment, $user, $source_project_id);
        $new_comment    = Comment::buildWithNewId($new_comment_id, $comment);

        $user_representation = MinimalUserRepresentation::build($user);

        $purifier            = \Codendi_HTMLPurifier::instance();
        $content_interpretor = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );

        return (new CommentRepresentationBuilder($purifier, $content_interpretor))->buildRepresentation($source_project_id, $user_representation, $new_comment);
    }

    /**
     * Get pull request's reviewers
     *
     * @url OPTIONS {id}/reviewers
     *
     * @param int $id Pull request ID
     */
    public function optionsReviewers(int $id): void
    {
        Header::allowOptionsGetPut();
    }

    /**
     * Get pull request's reviewers
     *
     * @url GET {id}/reviewers
     *
     * @access hybrid
     *
     * @param int $id Pull request ID
     *
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getReviewers(int $id): ReviewersRepresentation
    {
        $this->checkAccess();
        $this->optionsReviewers($id);

        $current_user = $this->user_manager->getCurrentUser();
        $pull_request = $this->getAccessiblePullRequestRetriever()->getAccessiblePullRequest($id, $current_user);

        $reviewer_retrievers = new ReviewerRetriever($this->user_manager, new ReviewerDAO(), $this->permission_checker);

        return ReviewersRepresentation::fromUsers(...$reviewer_retrievers->getReviewers($pull_request));
    }

    /**
     * Set pull request's reviewers
     *
     * @url PUT {id}/reviewers
     *
     * @status 204
     *
     * @param int $id Pull request ID
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putReviewers(int $id, ReviewersPUTRepresentation $representation): void
    {
        $this->checkAccess();
        $this->optionsReviewers($id);

        $pull_request = $this->getWritablePullRequestWithGitReference($id);

        $information_extractor = new ReviewerRepresentationInformationExtractor(
            new UserRESTReferenceRetriever($this->user_manager),
        );
        $users                 = $information_extractor->getUsers($representation);

        $reviewer_updater = new ReviewerUpdater(
            new ReviewerDAO(),
            $this->permission_checker,
            PullRequestNotificationSupport::buildDispatcher($this->logger)
        );
        try {
            $reviewer_updater->updatePullRequestReviewers(
                $pull_request->getPullRequest(),
                $this->user_manager->getCurrentUser(),
                new \DateTimeImmutable(),
                ...$users
            );
        } catch (UserCannotBeAddedAsReviewerException $exception) {
            throw new RestException(
                400,
                'User #' . $exception->getUser()->getId() . ' cannot access this pull request'
            );
        } catch (ReviewersCannotBeUpdatedOnClosedPullRequestException $exception) {
            throw new RestException(
                403,
                'This pull request is already closed, the reviewers can not be updated'
            );
        }
    }

    private function getWritablePullRequestWithGitReference($id): PullRequestWithGitReference
    {
        $current_user = $this->user_manager->getCurrentUser();

        $pull_request_with_git_reference = $this->getPullRequestWithGitReferenceRetriever()->getAccessiblePullRequestWithGitReferenceForCurrentUser($id, $current_user);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();

        $this->getPullRequestIsMergeableChecker()->checkUserCanMerge($pull_request, $current_user);

        return $pull_request_with_git_reference;
    }

    /**
     * @throws RestException 404
     */
    private function getRepository(int $repository_id): \GitRepository
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);
        if (! $repository) {
            throw new RestException(
                404,
                sprintf(dgettext('tuleap-pullrequest', 'Git repository #%d not found'), $repository_id)
            );
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
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForCommits()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLabels()
    {
        Header::allowOptionsGetPatch();
    }

    private function sendAllowHeadersForComments()
    {
        Header::allowOptionsGetPost();
    }

    private function sendAllowHeadersForInlineComments(): void
    {
        Header::allowOptionsPost();
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
        return new GitoliteAccessURLGenerator($this->git_plugin->getPluginInfo());
    }

    private function getPullRequestIsMergeableChecker(): PullRequestIsMergeableChecker
    {
        return new PullRequestIsMergeableChecker($this->permission_checker);
    }

    private function getAccessiblePullRequestRetriever(): AccessiblePullRequestRESTRetriever
    {
        return new AccessiblePullRequestRESTRetriever(
            new PullRequestRetriever($this->pull_request_dao),
            $this->permission_checker
        );
    }

    private function getPullRequestWithGitReferenceRetriever(): PullRequestWithGitReferenceRetriever
    {
        $git_pull_request_reference_dao       = new GitPullRequestReferenceDAO();
        $git_pull_request_reference_retriever = new GitPullRequestReferenceRetriever($git_pull_request_reference_dao);
        $git_pull_request_reference_updater   = new GitPullRequestReferenceUpdater(
            $git_pull_request_reference_dao,
            new GitPullRequestReferenceNamespaceAvailabilityChecker()
        );

        return new PullRequestWithGitReferenceRetriever(
            $git_pull_request_reference_retriever,
            $git_pull_request_reference_updater,
            $this->git_repository_factory,
            $this->getAccessiblePullRequestRetriever()
        );
    }
}
