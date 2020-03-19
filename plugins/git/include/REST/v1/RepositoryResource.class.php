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
use PluginFactory;
use ProjectHistoryDao;
use ProjectManager;
use SystemEventManager;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationDao;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\CIToken\Dao as CITokenDao;
use Tuleap\Git\CIToken\Manager as CITokenManager;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitDoesNotExistException;
use Tuleap\Git\CommitStatus\CommitStatusCreator;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\CommitStatus\InvalidCommitReferenceException;
use Tuleap\Git\Exceptions\DeletePluginNotInstalledException;
use Tuleap\Git\Exceptions\GitRepoRefNotFoundException;
use Tuleap\Git\Exceptions\RepositoryAlreadyInQueueForMigrationException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedOnRestrictedGerritServerException;
use Tuleap\Git\Exceptions\RepositoryNotMigratedException;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\GitPHP\Head;
use Tuleap\Git\GitPHP\ProjectProvider;
use Tuleap\Git\GitPHP\RepositoryAccessException;
use Tuleap\Git\GitPHP\RepositoryNotExistingException;
use Tuleap\Git\GitPHP\Tag;
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
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\Repository\GitRepositoryNameIsInvalidException;
use Tuleap\Git\Repository\RepositoryCreator;
use Tuleap\Git\XmlUgroupRetriever;
use Tuleap\Http\HttpClientFactory;
use Tuleap\PullRequest\REST\v1\RepositoryPullRequestRepresentation;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\v1\GitRepositoryRepresentationBase;
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
            $this->repository_factory
        );

        $this->gerrit_server_factory  = new Git_RemoteServer_GerritServerFactory(
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

        $git_plugin  = \PluginFactory::instance()->getPluginByName('git');
        $url_manager = new \Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());

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

        $mirror_data_mapper = new \Git_Mirror_MirrorDataMapper(
            new \Git_Mirror_MirrorDao(),
            $this->user_manager,
            $this->repository_factory,
            $this->project_manager,
            $this->git_system_event_manager,
            new \Git_Gitolite_GitoliteRCReader(new VersionDetector()),
            new \DefaultProjectMirrorDao()
        );

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

        $fine_grained_permission_factory    = new FineGrainedPermissionFactory(
            $fine_grained_dao,
            $ugroup_manager,
            $normalizer,
            $permissions_manager,
            $validator,
            $sorter,
            $xml_ugroup_retriever
        );
        $fine_grained_replicator            = new FineGrainedPermissionReplicator(
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
        $history_value_formatter            = new HistoryValueFormatter(
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
                    $this->git_system_event_manager,
                    $url_manager,
                    $git_dao,
                    new \Git_Mirror_MirrorDao(),
                    $git_plugin,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    new BigObjectAuthorizationManager(
                        new BigObjectAuthorizationDao(),
                        ProjectManager::instance()
                    ),
                    new VersionDetector()
                ),
                new GitoliteAccessURLGenerator($git_plugin->getPluginInfo()),
                \BackendLogger::getDefaultLogger(\GitPlugin::LOG_IDENTIFIER),
            ),
            $mirror_data_mapper,
            new \GitRepositoryManager(
                $this->repository_factory,
                $this->git_system_event_manager,
                $git_dao,
                "",
                new \GitRepositoryMirrorUpdater($mirror_data_mapper, $project_history_dao),
                $mirror_data_mapper,
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

        $status_retriever   = new CommitStatusRetriever(new CommitStatusDAO);
        $metadata_retriever = new CommitMetadataRetriever($status_retriever, $this->user_manager);
        $url_manager        = new Git_GitRepositoryUrlManager(
            PluginFactory::instance()->getPluginByName('git'),
            new \Tuleap\InstanceBaseURLBuilder()
        );

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

        return $this->representation_builder->build($user, $repository, GitRepositoryRepresentationBase::FIELDS_ALL);
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
     * Get git repository's pull requests
     *
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * <p>
     * $query parameter is optional, by default we return all pull requests. If
     * query={"status":"open"} then only open pull requests are returned and if
     * query={"status":"closed"} then only closed pull requests are returned.
     * </p>
     *
     * @url GET {id}/pull_requests
     *
     * @access protected
     *
     * @param int    $id     Id of the repository
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param int    $limit  Number of elements displayed per page {@from path}
     * @param int    $offset Position of the first element to display {@from path}
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
        $this->checkLimit($limit);

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
     * @param string $state            {@choice failure,success} {@from body}
     * @param string $token            {@from body}
     *
     * @status 201
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 400
     */
    public function postCommitStatus($id_or_path, $commit_reference, $state, $token)
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

        $repo_ci_token = $this->ci_token_manager->getToken($repository);
        if ($repo_ci_token === null || ! \hash_equals($token, $repo_ci_token)) {
            throw new RestException(403, 'Invalid token');
        }

        $commit_status_creator = new CommitStatusCreator(new CommitStatusDAO);

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
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int    $id    Id of the Git repository
     * @param GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit {@from body}{@required false}
     * @param string $disconnect_from_gerrit {@from body}{@required false} {@choice delete,read-only,noop}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function patchId(
        $id,
        ?GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit = null,
        $disconnect_from_gerrit = null
    ) {
        $this->checkAccess();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository->getProject()
        );

        if (! $repository->userCanAdmin($user)) {
            throw new RestException(403, 'User is not allowed to migrate repository');
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

        $this->sendAllowHeaders();
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
    public function optionsGetBranches($id)
    {
        Header::allowOptionsGet();
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
     * @throws RestException 406
     */
    public function getBranches($id, $offset = 0, $limit = self::MAX_LIMIT)
    {
        $this->checkAccess();
        $this->checkLimit($limit);

        $repository = $this->getRepositoryForCurrentUser($id);

        try {
            $project = $this->getGitPHPProject($id);
        } catch (RepositoryNotExistingException $ex) {
            $this->sendAllowHeaders();
            Header::sendPaginationHeaders($limit, $offset, 0, self::MAX_LIMIT);
            return [];
        }

        /** @var Head[] $branches_refs */
        $branches_refs        = $project->GetHeads();
        $total_size           = count($branches_refs);
        $sliced_branches_refs = array_slice($branches_refs, $offset, $limit);

        $commits = [];
        foreach ($sliced_branches_refs as $branch) {
            try {
                $commits[] = $branch->GetCommit();
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the tag if by any chance it is invalid
            }
        }

        $commit_representation_collection = $this->commit_representation_builder->build($repository, ...$commits);

        $result = [];
        foreach ($sliced_branches_refs as $branch) {
            $name = $branch->GetName();
            try {
                $commit_representation = $commit_representation_collection->getRepresentation($branch->GetCommit());

                $branch_representation = new GitBranchRepresentation();
                $branch_representation->build($name, $commit_representation);

                $result[] = $branch_representation;
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the branch if by any chance it is invalid
            }
        }

        $this->sendAllowHeaders();
        Header::sendPaginationHeaders($limit, $offset, $total_size, self::MAX_LIMIT);

        return $result;
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
     * @throws RestException 406
     */
    public function getTags($id, $offset = 0, $limit = self::MAX_LIMIT)
    {
        $this->checkAccess();
        $this->checkLimit($limit);

        $repository = $this->getRepositoryForCurrentUser($id);

        try {
            $project = $this->getGitPHPProject($id);
        } catch (RepositoryNotExistingException $ex) {
            $this->sendAllowHeaders();
            Header::sendPaginationHeaders($limit, $offset, 0, self::MAX_LIMIT);
            return [];
        }

        /** @var Tag[] $tags_refs */
        $tags_refs        = $project->GetTags();
        $total_size       = count($tags_refs);
        $sliced_tags_refs = array_slice($tags_refs, $offset, $limit);

        $commits = [];
        foreach ($sliced_tags_refs as $tag) {
            try {
                $commits[] = $tag->GetCommit();
            } catch (GitRepoRefNotFoundException $e) {
                // ignore the tag if by any chance it is invalid
            }
        }

        $commit_representation_collection = $this->commit_representation_builder->build($repository, ...$commits);

        $result = [];
        foreach ($sliced_tags_refs as $tag) {
            $name = $tag->GetName();
            try {
                $commit_representation = $commit_representation_collection->getRepresentation($tag->GetCommit());

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
        GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit
    ) {
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
            return $this->migration_handler->migrate($repository, $server_id, $permissions, $user);
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

    private function getRepository(PFUser $user, $id)
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
            array(
                'version'    => 'v1',
                'repository' => $repository,
                'query'      => $query,
                'limit'      => $limit,
                'offset'     => $offset,
                'result'     => &$result
            )
        );

        return $result;
    }

    private function checkPullRequestEndpointsAvailable()
    {
        $available = false;

        EventManager::instance()->processEvent(
            REST_GIT_PULL_REQUEST_ENDPOINTS,
            array(
                'available' => &$available
            )
        );

        if ($available === false) {
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

    private function checkLimit($limit)
    {
        if ($limit > self::MAX_LIMIT) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    /**
     * @param $id
     *
     * @return GitRepository
     * @throws RestException
     */
    private function getRepositoryForCurrentUser($id)
    {
        $user = $this->getCurrentUser();

        return $this->getRepository($user, $id);
    }
}
