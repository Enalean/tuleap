<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use EventManager;
use Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use Git_Exec;
use Git_GitRepositoryUrlManager;
use Git_PermissionsDao;
use Git_RemoteServer_Dao;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_NotFoundException;
use Git_SystemEventManager;
use GitDao;
use GitPermissionsManager;
use GitRepoNotFoundException;
use GitRepoNotReadableException;
use GitRepository;
use GitRepositoryAlreadyExistsException;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use PFUser;
use ProjectHistoryDao;
use ProjectManager;
use SystemEventManager;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationDao;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\Branch\BranchCreationExecutor;
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionDAO;
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionManager;
use Tuleap\Git\CIBuilds\CITokenDao;
use Tuleap\Git\CIBuilds\CITokenManager;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitDoesNotExistException;
use Tuleap\Git\CommitStatus\CommitStatusCreator;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\CommitStatus\InvalidCommitReferenceException;
use Tuleap\Git\DefaultBranch\CannotSetANonExistingBranchAsDefaultException;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdateExecutorAsGitoliteUser;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdater;
use Tuleap\Git\Exceptions\DeletePluginNotInstalledException;
use Tuleap\Git\Exceptions\GitRepoRefNotFoundException;
use Tuleap\Git\Exceptions\RepositoryAlreadyInQueueForMigrationException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedOnRestrictedGerritServerException;
use Tuleap\Git\Exceptions\RepositoryNotMigratedException;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\GitPHP\Pack;
use Tuleap\Git\GitPHP\ProjectProvider;
use Tuleap\Git\GitPHP\RepositoryAccessException;
use Tuleap\Git\GitPHP\RepositoryNotExistingException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedPatternValidator;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedPermissionSorter;
use Tuleap\Git\Permissions\FineGrainedRegexpValidator;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Permissions\PatternValidator;
use Tuleap\Git\Permissions\RegexpFineGrainedDao;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpRepositoryDao;
use Tuleap\Git\Permissions\RegexpTemplateDao;
use Tuleap\Git\PullRequestEndpointsAvailableChecker;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\Repository\GitRepositoryNameIsInvalidException;
use Tuleap\Git\Repository\RepositoryCreator;
use Tuleap\Git\REST\v1\Branch\BranchCreator;
use Tuleap\Git\XmlUgroupRetriever;
use Tuleap\Http\HttpClientFactory;
use Tuleap\PullRequest\REST\v1\RepositoryPullRequestRepresentation;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use UserManager;

include_once __DIR__ . '/../../../../../src/www/project/admin/permissions.php';

class RepositoryResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    public const MIGRATE_PERMISSION_DEFAULT = 'default';
    public const MIGRATE_NO_PERMISSION      = 'none';
    /**
     * @var RepositoryCreator
     */
    private $repository_creator;
    /**
     * @var GitPermissionsManager
     */
    private $git_permission_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var UserManager
     */
    private $user_manager;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var RepositoryRepresentationBuilder */
    private $representation_builder;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    /** @var MigrationHandler */
    private $migration_handler;

    /**
     * @var CITokenManager
     */
    private $ci_token_manager;
    /**
     * @var GitCommitRepresentationBuilder
     */
    private $commit_representation_builder;

    public function __construct()
    {
        $git_dao               = new GitDao();
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $event_manager         = EventManager::instance();

        $this->repository_factory = new GitRepositoryFactory(
            $git_dao,
            $this->project_manager
        );

        $this->git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
        );

        $this->gerrit_server_factory = new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            $git_dao,
            $this->git_system_event_manager,
            $this->project_manager
        );

        $fine_grained_dao       = new FineGrainedDao();
        $fine_grained_retriever = new FineGrainedRetriever($fine_grained_dao);

        $this->git_permission_manager = new GitPermissionsManager(
            new Git_PermissionsDao(),
            $this->git_system_event_manager,
            $fine_grained_dao,
            $fine_grained_retriever
        );

        $git_plugin = \PluginFactory::instance()->getPluginByName('git');
        assert($git_plugin instanceof \GitPlugin);
        $url_manager = new \Git_GitRepositoryUrlManager($git_plugin);

        $this->representation_builder = new RepositoryRepresentationBuilder(
            $this->git_permission_manager,
            $this->gerrit_server_factory,
            new \Git_LogDao(),
            $event_manager,
            $url_manager
        );

        $project_history_dao     = new ProjectHistoryDao();
        $this->migration_handler = new MigrationHandler(
            $this->git_system_event_manager,
            $this->gerrit_server_factory,
            new Git_Driver_Gerrit_GerritDriverFactory(
                new \Tuleap\Git\Driver\GerritHTTPClientFactory(HttpClientFactory::createClient()),
                \Tuleap\Http\HTTPFactoryBuilder::requestFactory(),
                \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
                \BackendLogger::getDefaultLogger(\GitPlugin::LOG_IDENTIFIER),
            ),
            $project_history_dao,
            new Git_Driver_Gerrit_ProjectCreatorStatus(new Git_Driver_Gerrit_ProjectCreatorStatusDao()),
            $this->project_manager
        );

        $this->ci_token_manager = new CITokenManager(new CITokenDao());

        $regexp_retriever     = new RegexpFineGrainedRetriever(
            new RegexpFineGrainedDao(),
            new RegexpRepositoryDao(),
            new RegexpTemplateDao()
        );
        $ugroup_manager       = new \UGroupManager();
        $normalizer           = new \PermissionsNormalizer();
        $permissions_manager  = new \PermissionsManager(new \PermissionsDao());
        $validator            = new PatternValidator(
            new FineGrainedPatternValidator(),
            new FineGrainedRegexpValidator(),
            $regexp_retriever
        );
        $sorter               = new FineGrainedPermissionSorter();
        $xml_ugroup_retriever = new XmlUgroupRetriever(\BackendLogger::getDefaultLogger(\GitPlugin::LOG_IDENTIFIER), $ugroup_manager);

        $fine_grained_permission_factory = new FineGrainedPermissionFactory(
            $fine_grained_dao,
            $ugroup_manager,
            $normalizer,
            $permissions_manager,
            $validator,
            $sorter,
            $xml_ugroup_retriever
        );
        $fine_grained_replicator         = new FineGrainedPermissionReplicator(
            $fine_grained_dao,
            new DefaultFineGrainedPermissionFactory(
                $fine_grained_dao,
                $ugroup_manager,
                $normalizer,
                $permissions_manager,
                $validator,
                $sorter
            ),
            new FineGrainedPermissionSaver(
                $fine_grained_dao
            ),
            $fine_grained_permission_factory,
            new RegexpFineGrainedEnabler(
                new RegexpFineGrainedDao(),
                new RegexpRepositoryDao(),
                new RegexpTemplateDao()
            ),
            $regexp_retriever,
            $validator
        );
        $history_value_formatter         = new HistoryValueFormatter(
            $permissions_manager,
            $ugroup_manager,
            $fine_grained_retriever,
            new DefaultFineGrainedPermissionFactory(
                $fine_grained_dao,
                $ugroup_manager,
                $normalizer,
                $permissions_manager,
                $validator,
                $sorter
            ),
            $fine_grained_permission_factory
        );

        $this->repository_creator = new RepositoryCreator(
            $this->repository_factory,
            new \Git_Backend_Gitolite(
                new \Git_GitoliteDriver(
                    \BackendLogger::getDefaultLogger(\GitPlugin::LOG_IDENTIFIER),
                    $url_manager,
                    $git_dao,
                    $git_plugin,
                    new BigObjectAuthorizationManager(
                        new BigObjectAuthorizationDao(),
                        ProjectManager::instance()
                    ),
                    null,
                    null,
                    null,
                    null,
                    null,
                ),
                new GitoliteAccessURLGenerator($git_plugin->getPluginInfo()),
                new DefaultBranchUpdateExecutorAsGitoliteUser(),
                \BackendLogger::getDefaultLogger(\GitPlugin::LOG_IDENTIFIER),
            ),
            new \GitRepositoryManager(
                $this->repository_factory,
                $this->git_system_event_manager,
                $git_dao,
                "",
                $fine_grained_replicator,
                $project_history_dao,
                $history_value_formatter,
                $event_manager
            ),
            $this->git_permission_manager,
            $fine_grained_replicator,
            $project_history_dao,
            $history_value_formatter,
            $this->ci_token_manager,
            $event_manager
        );

        $status_retriever   = new CommitStatusRetriever(new CommitStatusDAO());
        $metadata_retriever = new CommitMetadataRetriever($status_retriever, $this->user_manager);
        $url_manager        = new Git_GitRepositoryUrlManager($git_plugin);

        $this->commit_representation_builder = new GitCommitRepresentationBuilder($metadata_retriever, $url_manager);
    }

    /**
     * Return info about repository if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the repository
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * @access hybrid
     *
     * @param int $id Id of the repository
     * @return GitRepositoryRepresentation | null
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function get($id)
    {
        $this->checkAccess();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);

        $this->sendAllowHeaders();

        return $this->representation_builder->build($user, $repository, GitRepositoryRepresentation::FIELDS_ALL);
    }

    /**
     * @url OPTIONS {id}/pull_requests
     *
     * @param int $id Id of the repository
     *
     * @throws RestException 404
     */
    public function optionsPullRequests($id)
    {
        $this->checkPullRequestEndpointsAvailable();
        $this->sendAllowHeaders();
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
     *         <b>Author</b>: <code>query={"author": {"id": int}}</code> where "id" is the user_id of the author.
     *     </p>
     * </p>
     *
     * <p>
     *     All these filters are cumulative. For instance, <code>query={"status": "closed", "author": {"id": 102 }}</code>
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
     * @param int    $id     Id of the repository
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int    $limit  Number of elements displayed per page {@from path} {@min 0} {@max 50}
     * @param int    $offset Position of the first element to display {@from path} {@min 0}
     *
     * @return RepositoryPullRequestRepresentation
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getPullRequests($id, $query = '', $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        $this->checkPullRequestEndpointsAvailable();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);
        $result     = $this->getPaginatedPullRequests($repository, $query, $limit, $offset);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, $result->total_size);

        return $result;
    }

    /**
     * Post
     *
     * @url    POST
     *
     * @access hybrid
     *
     *
     * @param $project_id {@type int} {@from body} project id
     * @param $name       {@type string} {@from body} Repository name
     *
     * @status 201
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     * @throws RestException 500
     */
    public function post($project_id, $name)
    {
        $this->checkAccess();

        Header::allowOptionsPost();

        $user    = $this->user_manager->getCurrentUser();
        $project = $this->project_manager->getProject($project_id);
        if ($project->isError()) {
            throw new RestException(404, "Given project does not exist");
        }

        if (! $project->usesService(\GitPlugin::SERVICE_SHORTNAME)) {
            throw new RestException(400, "Project does not use Git service");
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $project
        );

        if (! $this->git_permission_manager->userIsGitAdmin($user, $project)) {
            throw new RestException(403, "User does not have permissions to create a Git Repository");
        }
        try {
            $repository = $this->repository_creator->create($project, $user, $name);
        } catch (GitRepositoryNameIsInvalidException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (GitRepositoryAlreadyExistsException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (Exception $e) {
            throw new RestException(500, $e->getMessage());
        }

        return $this->get($repository->getId());
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptionsPost();
    }

    /**
     * @url OPTIONS {id}/statuses/{commit_reference}
     *
     * @param int $id Git repository id
     * @param string $commit_reference Commit SHA-1
     */
    public function optionsCommitStatus($id, $commit_reference)
    {
        Header::allowOptionsPost();
    }

    /**
     * Post a commit status
     *
     * @url    POST {id_or_path}/statuses/{commit_reference}
     *
     * @access hybrid
     *
     * @param string $id_or_path       Git repository id or Git repository path
     * @param string $commit_reference Commit SHA-1
     * @param string $state            {@choice failure,success,pending} {@from body}
     * @param string $token            {@from body}{@required false}
     *
     * @status 201
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 400
     */
    public function postCommitStatus($id_or_path, $commit_reference, $state, $token = null)
    {
        if (ctype_digit($id_or_path)) {
            $repository = $this->repository_factory->getRepositoryById((int) $id_or_path);
        } else {
            preg_match("/(.+?)\/(.+)/", $id_or_path, $path);
            if (count($path) !== 3) {
                throw new RestException(400, 'Bad repository path format');
            }
            $repository = $this->repository_factory->getByProjectNameAndPath($path[1], $path[2]);
        }
        if (! $repository) {
            throw new RestException(404, 'Repository not found.');
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository->getProject()
        );

        if ($token !== null) {
            $this->checkCITokenValidity($repository, $token);
        } else {
            $this->checkUserHasPermission($repository);
        }

        $commit_status_creator = new CommitStatusCreator(new CommitStatusDAO());

        try {
            $commit_status_creator->createCommitStatus(
                $repository,
                Git_Exec::buildFromRepository($repository),
                $commit_reference,
                $state
            );
        } catch (CommitDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (InvalidCommitReferenceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @throws RestException
     */
    private function checkCITokenValidity(GitRepository $repository, string $ci_token): void
    {
        $repo_ci_token = $this->ci_token_manager->getToken($repository);
        if ($repo_ci_token === null || ! \hash_equals($ci_token, $repo_ci_token)) {
            throw new RestException(403, 'Invalid token');
        }
    }

    /**
     * @throws RestException
     */
    private function checkUserHasPermission(GitRepository $repository): void
    {
        $user                                = $this->user_manager->getCurrentUser();
        $set_build_status_permission_manager = new BuildStatusChangePermissionManager(
            new BuildStatusChangePermissionDAO()
        );

        if (
            ! $set_build_status_permission_manager->canUserSetBuildStatusInRepository($user, $repository)
        ) {
            throw new RestException(403, 'You are not allowed to set the build status');
        }
    }

    /**
     * Patch Git repository
     *
     * Patch properties of a given Git repository
     *
     * <pre>
     * /!\ This REST route is under construction and subject to changes /!\
     * </pre>
     *
     * <br>
     * To migrate a repository in Gerrit:
     * <pre>
     * {<br>
     * &nbsp;"migrate_to_gerrit": {<br/>
     * &nbsp;&nbsp;"server": 1,<br/>
     * &nbsp;&nbsp;"permissions": "default"<br/>
     * &nbsp;}<br/>
     * }
     * </pre>
     *
     * <br>
     * To disconnect a repository in Gerrit:
     * <pre>
     * {<br>
     * &nbsp;"disconnect_from_gerrit": "read-only"<br/>
     * }
     * </pre>
     * <br>
     *
     * To change the default branch of a repository:
     * <pre>
     * {<br>
     * &nbsp;"default_branch": "dev"<br/>
     * }
     * </pre>
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int    $id    Id of the Git repository
     * @param GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit {@from body}{@required false}
     * @param string $disconnect_from_gerrit {@from body}{@required false} {@choice delete,read-only,noop}
     * @param string $default_branch {@from body}{@required false} New default branch to set, the branch needs to exist
     * @psalm-param string|null $default_branch
     *
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function patchId(
        $id,
        ?GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit = null,
        $disconnect_from_gerrit = null,
        ?string $default_branch = null,
    ) {
        $this->checkAccess();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository->getProject()
        );

        if (! $repository->userCanAdmin($user)) {
            throw new RestException(403, 'User is not allowed to administrate this repository');
        }

        if ($migrate_to_gerrit && $disconnect_from_gerrit) {
            throw new RestException(403, 'Bad request. You can only migrate or disconnect a Git repository');
        }

        if ($migrate_to_gerrit) {
            $this->migrate($repository, $user, $migrate_to_gerrit);
        }

        if ($disconnect_from_gerrit) {
            $this->disconnect($repository, $disconnect_from_gerrit);
        }

        if ($default_branch !== null) {
            $default_branch_updater = new DefaultBranchUpdater(new DefaultBranchUpdateExecutorAsGitoliteUser());
            try {
                $default_branch_updater->updateDefaultBranch(Git_Exec::buildFromRepository($repository), $default_branch);
            } catch (CannotSetANonExistingBranchAsDefaultException $exception) {
                throw new RestException(400, $exception->getMessage(), [], $exception);
            }
        }

        $this->sendAllowHeaders();
    }

    /**
     * Get the tree of a git repository.
     *
     * Returns the repository root content when the path is not given.
     *
     * @url    GET {id}/tree
     *
     * @access hybrid
     *
     * @param int    $id   Id of the git repository
     * @param string $ref  reference {@from path} {@required true}
     * @param string $path path of the file {@from path} {@required false}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     * @param int $limit  Number of elements displayed {@from path}{@min 1}{@max 50}
     *
     * @return array {@type Tuleap\Git\REST\v1\GitTreeRepresentation}
     *
     * @status 200
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     *
     */
    public function getTree(int $id, string $ref, string $path = "", int $offset = 0, int $limit = self::MAX_LIMIT): array
    {
        $this->checkAccess();
        $tree_representation_factory = new GitTreeRepresentationFactory();
        try {
            $repository   = $this->getGitPHPProject($id);
            $tree_content = $tree_representation_factory->getGitTreeRepresentation(
                rtrim($path, DIRECTORY_SEPARATOR),
                $ref,
                $repository
            );
            $result       = array_slice(
                $tree_content,
                $offset,
                $limit
            );

            Header::allowOptionsGet();
            Header::sendPaginationHeaders($limit, $offset, count($tree_content), self::MAX_LIMIT);

            return $result;
        } catch (\GitRepositoryException | GitRepoRefNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (RepositoryNotExistingException $exception) {
            throw new RestException(404, "Reference $ref does not exist");
        } catch (GitObjectTypeNotSupportedException $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @url OPTIONS {id}/tree
     *
     * @param int    $id           Id of the git repository
     * @param string $ref          ref {@from path} {@required true}
     * @param string $path         path {@from path} {@required false}
     */
    public function optionsGetTree(int $id, string $ref, string $path = ""): void
    {
        Header::allowOptionsGet();
    }

    /**
     * @url OPTIONS {id}/files
     *
     * @param int    $id           Id of the git repository
     * @param string $path_to_file path of the file {@from path}
     * @param string $ref          ref {@from path}
     */
    public function optionsGetFileContent($id, $path_to_file, $ref)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the content of a specific file from a git repository.
     *
     * The file size is in Bytes. <br/>
     * If no ref given, master is used.
     *
     * @url    GET {id}/files
     *
     * @access hybrid
     *
     * @param int    $id           Id of the git repository
     * @param string $path_to_file path of the file {@from path}
     * @param string $ref          reference {@from path}
     *
     * @return GitFileContentRepresentation
     *
     * @status 200
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     *
     */
    public function getFileContent($id, $path_to_file, $ref = 'master')
    {
        Header::allowOptionsGet();

        $this->checkAccess();
        $file_representation_factory = new GitFileRepresentationFactory();
        try {
            $repository = $this->getGitPHPProject($id);
            $result     = $file_representation_factory->getGitFileRepresentation(
                $path_to_file,
                $ref,
                $repository
            );

            return $result;
        } catch (\GitRepositoryException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (GitRepoRefNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (RepositoryNotExistingException $exception) {
            throw new RestException(404, "Reference $ref does not exist");
        }
    }

    /**
     * @url OPTIONS {id}/branches
     *
     * @param int $id Id of the git repository
     */
    public function optionsGetPostBranches($id): void
    {
        Header::allowOptionsGetPost();
    }

    /**
     * Get all the branches of a git repository
     *
     * @url    GET {id}/branches
     *
     * @access hybrid
     *
     * @param int $id     Id of the git repository
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     * @param int $limit  Number of elements displayed {@from path}{@min 1}{@max 50}
     *
     * @return array {@type \Tuleap\Git\REST\v1\GitBranchRepresentation}
     *
     * @status 200
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getBranches($id, $offset = 0, $limit = self::MAX_LIMIT)
    {
        $this->checkAccess();

        $repository = $this->getRepositoryForCurrentUser($id);

        try {
            $project = $this->getGitPHPProject($id);
        } catch (RepositoryNotExistingException $ex) {
            $this->sendAllowHeaders();
            Header::sendPaginationHeaders($limit, $offset, 0, self::MAX_LIMIT);
            return [];
        }

        $branches_refs        = $project->GetHeads();
        $total_size           = count($branches_refs);
        $sliced_branches_refs = array_slice($branches_refs, $offset, $limit);

        $commits = [];
        foreach ($sliced_branches_refs as $branch) {
            try {
                $commit = $project->GetCommit($branch);
                if ($commit !== null) {
                    $commits[] = $commit;
                }
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the branch if by any chance it is invalid
            }
        }

        $commit_representation_collection = $this->commit_representation_builder->buildCollection($repository, ...$commits);

        $result = [];
        foreach ($sliced_branches_refs as $branch) {
            $name = $branch;
            try {
                $commit = $project->GetCommit($branch);
                if ($commit !== null) {
                    $commit_representation = $commit_representation_collection->getRepresentation($commit);
                    $branch_representation = GitBranchRepresentation::build(
                        $name,
                        $repository,
                        $commit_representation
                    );

                    $result[] = $branch_representation;
                }
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the branch if by any chance it is invalid
            }
        }

        $this->optionsGetPostBranches($id);
        Header::sendPaginationHeaders($limit, $offset, $total_size, self::MAX_LIMIT);

        return $result;
    }

    /**
     * Create a Git branch
     *
     * Create a branch in a git repository.<br/>
     * To create a branch, you have to provide the branch name and the reference (another branch name or a commit SHA1)
     *
     * @url POST {id}/branches
     *
     * @access protected
     *
     * @param int $id Id of the git repository
     * @param GitBranchPOSTRepresentation $representation The representation of the POST data {@from body}
     *
     * @status 201
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     */
    public function createBranch($id, GitBranchPOSTRepresentation $representation): GitBranchRepresentation
    {
        $this->checkAccess();
        $this->optionsGetPostBranches($id);

        $repository = $this->getRepositoryForCurrentUser($id);

        $branch_creator = new BranchCreator(
            new Git_Exec($repository->getFullPath(), $repository->getFullPath()),
            new BranchCreationExecutor(),
            new AccessControlVerifier(
                new FineGrainedRetriever(
                    new FineGrainedDao(),
                ),
                new \System_Command(),
            )
        );

        $branch_creator->createBranch(
            $this->getCurrentUser(),
            $repository,
            $representation
        );

        try {
            $gitphp_project = $this->getGitPHPProject($id);
            $commit         = $gitphp_project->GetCommit($representation->branch_name);

            if ($commit === null) {
                throw new RestException(
                    500,
                    "Associated commit not found"
                );
            }

            $commit_representation = $this->commit_representation_builder->build($repository, $commit);
            return GitBranchRepresentation::build(
                $representation->branch_name,
                $repository,
                $commit_representation
            );
        } catch (RepositoryNotExistingException $ex) {
            throw new RestException(
                500,
                "GitPHP project not found for repository"
            );
        }
    }

    /**
     * @url OPTIONS {id}/tags
     *
     * @param int $id Id of the git repository
     */
    public function optionsGetTags($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get all the tags of a git repository
     *
     * @url    GET {id}/tags
     *
     * @access hybrid
     *
     * @param int $id     Id of the git repository
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     * @param int $limit  Number of elements displayed {@from path}{@min 1}{@max 50}
     *
     * @return array {@type \Tuleap\Git\REST\v1\GitTagRepresentation}
     *
     * @status 200
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getTags($id, $offset = 0, $limit = self::MAX_LIMIT)
    {
        $this->checkAccess();

        $repository = $this->getRepositoryForCurrentUser($id);

        try {
            $project = $this->getGitPHPProject($id);
        } catch (RepositoryNotExistingException $ex) {
            $this->sendAllowHeaders();
            Header::sendPaginationHeaders($limit, $offset, 0, self::MAX_LIMIT);
            return [];
        }

        $tags_refs        = $project->GetTags();
        $total_size       = count($tags_refs);
        $sliced_tags_refs = array_slice($tags_refs, $offset, $limit);
        $commits          = [];

        foreach ($sliced_tags_refs as $tag) {
            try {
                $commit = $project->getCommit($tag);
                if ($commit) {
                    $commits[] = $commit;
                }
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the tag if by any chance it is invalid
            }
        }

        $commit_representation_collection = $this->commit_representation_builder->buildCollection($repository, ...$commits);

        $result = [];
        foreach ($sliced_tags_refs as $tag) {
            $name   = $tag;
            $commit = $project->getCommit($tag);
            if (! $commit) {
                continue;
            }

            try {
                $commit_representation = $commit_representation_collection->getRepresentation($commit);

                $tag_representation = new GitTagRepresentation();
                $tag_representation->build($name, $commit_representation);

                $result[] = $tag_representation;
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the tag if by any chance it is invalid
            }
        }

        $this->sendAllowHeaders();
        Header::sendPaginationHeaders($limit, $offset, $total_size, self::MAX_LIMIT);

        return $result;
    }

    /**
     * @url OPTIONS {id}/commits/{commit_reference}
     *
     * @param int $id Id of the repository
     * @param string $commit_reference Commit SHA-1
     */
    public function optionsGetCommits(int $id, string $commit_reference): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get a commit
     *
     * @url GET {id}/commits/{commit_reference}
     *
     * @access hybrid
     *
     * @param int $id      Git repository id
     * @param string $commit_reference Commit reference (sha-1, branch etc...)
     *
     *
     * @status 200
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getCommits(int $id, string $commit_reference): GitCommitRepresentation
    {
        $this->checkAccess();

        try {
            $project = $this->getGitPHPProject($id);
        } catch (RepositoryNotExistingException $exception) {
            throw new RestException(404, 'Commit not found');
        }

        $commit = $project->GetCommit($commit_reference);
        if (! $commit) {
             throw new RestException(404, 'Commit not found');
        }

        $type = 0;
        if ($project->GetObject($commit->GetHash(), $type) === false || $type !== Pack::OBJ_COMMIT) {
            throw new RestException(404, 'Commit not found');
        }

        $repository = $this->getRepositoryForCurrentUser($id);

        Header::allowOptionsGet();

        return $this->commit_representation_builder->build($repository, $commit);
    }

    /**
     * @return \Tuleap\Git\GitPHP\Project
     * @throws RepositoryNotExistingException
     * @throws RepositoryAccessException
     * @throws RestException
     */
    private function getGitPHPProject($repository_id)
    {
        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $repository_id);
        $provider   = new ProjectProvider($repository);

        return $provider->GetProject();
    }

    private function disconnect(GitRepository $repository, $disconnect_from_gerrit)
    {
        try {
            $this->migration_handler->disconnect($repository, $disconnect_from_gerrit);
        } catch (DeletePluginNotInstalledException $e) {
            throw new RestException(400, 'Gerrit delete plugin not installed.');
        } catch (RepositoryNotMigratedException $e) {
            //Do nothing
        }
    }

    private function migrate(
        GitRepository $repository,
        PFUser $user,
        GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit,
    ): void {
        $server_id   = $migrate_to_gerrit->server;
        $permissions = $migrate_to_gerrit->permissions;

        if ($permissions !== self::MIGRATE_NO_PERMISSION && $permissions !== self::MIGRATE_PERMISSION_DEFAULT) {
            throw new RestException(
                400,
                'Invalid permission provided. Valid values are ' .
                self::MIGRATE_NO_PERMISSION . ' or ' . self::MIGRATE_PERMISSION_DEFAULT
            );
        }

        try {
            $this->migration_handler->migrate($repository, $server_id, $permissions, $user);
        } catch (RepositoryCannotBeMigratedOnRestrictedGerritServerException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (RepositoryCannotBeMigratedException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (Git_RemoteServer_NotFoundException $exception) {
            throw new RestException(400, 'Gerrit server does not exist');
        } catch (RepositoryAlreadyInQueueForMigrationException $exception) {
            //Do nothing
        }
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    /**
     * @return GitRepository|\GitRepositoryGitoliteAdmin
     *
     * @throws RestException
     */
    private function getRepository(PFUser $user, $id): GitRepository
    {
        try {
            $repository = $this->repository_factory->getRepositoryByIdUserCanSee($user, $id);
        } catch (GitRepoNotReadableException $exception) {
            throw new RestException(403, 'Git repository not accessible for user');
        } catch (GitRepoNotFoundException $exception) {
            throw new RestException(404, 'Git repository not found');
        } catch (Exception $exception) {
            throw new RestException(403, 'Project not accessible for user');
        }

        return $repository;
    }

    private function getPaginatedPullRequests(GitRepository $repository, $query, $limit, $offset)
    {
        $result = null;

        EventManager::instance()->processEvent(
            REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY,
            [
                'version'    => 'v1',
                'repository' => $repository,
                'query'      => $query,
                'limit'      => $limit,
                'offset'     => $offset,
                'result'     => &$result,
            ]
        );

        return $result;
    }

    private function checkPullRequestEndpointsAvailable()
    {
        $checker = new PullRequestEndpointsAvailableChecker(EventManager::instance());

        if (! $checker->arePullRequestEndpointsAvailable()) {
            throw new RestException(404, 'PullRequest plugin not activated');
        }
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * @throws RestException
     */
    private function getRepositoryForCurrentUser($id): GitRepository
    {
        $user = $this->getCurrentUser();

        return $this->getRepository($user, $id);
    }
}
