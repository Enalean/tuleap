<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use BackendSVN;
use EventManager;
use Exception;
use ForgeConfig;
use HTTPRequest;
use Psr\Log\LoggerInterface;
use Project;
use ProjectManager;
use System_Command;
use SystemEvent;
use SystemEventManager;
use Tuleap\Event\Events\ArchiveDeletedItemEvent;
use Tuleap\Event\Events\ArchiveDeletedItemFileProvider;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Events\SystemEvent_SVN_RESTORE_REPOSITORY;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\SvnAdmin;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\SvnCoreUsage;

class RepositoryManager
{
    public const PREFIX = 'svn';

    /** @var Dao */
    private $dao;
     /** @var ProjectManager */
    private $project_manager;
     /** @var SvnAdmin */
    private $svnadmin;
     /** @var LoggerInterface */
    private $logger;
    /** @var System_Command */
    private $system_command;
    /** @var Destructor */
    private $destructor;
    /** @var EventManager */
    private $event_manager;
    /** @var BackendSVN */
    private $backend;
    /** @var AccessFileHistoryFactory */
    private $access_file_history_factory;

    public function __construct(
        Dao $dao,
        ProjectManager $project_manager,
        SvnAdmin $svnadmin,
        LoggerInterface $logger,
        System_Command $system_command,
        Destructor $destructor,
        EventManager $event_manager,
        BackendSVN $backend,
        AccessFileHistoryFactory $access_file_history_factory,
    ) {
        $this->dao                         = $dao;
        $this->project_manager             = $project_manager;
        $this->svnadmin                    = $svnadmin;
        $this->logger                      = $logger;
        $this->system_command              = $system_command;
        $this->destructor                  = $destructor;
        $this->event_manager               = $event_manager;
        $this->backend                     = $backend;
        $this->access_file_history_factory = $access_file_history_factory;
    }

    /**
     * @return Repository[]
     */
    public function getRepositoriesInProject(Project $project)
    {
        $repositories = [];
        foreach ($this->dao->searchByProject($project) as $row) {
            $repositories[] = $this->instantiateFromRow($row, $project);
        }

        return $repositories;
    }

    /**
     * @return RepositoryPaginatedCollection
     */
    public function getRepositoryPaginatedCollection(Project $project, $limit, $offset)
    {
        $repositories = [];
        foreach ($this->dao->searchPaginatedByProject($project, $limit, $offset) as $row) {
            $repositories[] = $this->instantiateFromRow($row, $project);
        }

        return new RepositoryPaginatedCollection(
            $repositories,
            $this->dao->foundRows()
        );
    }

    /**
     * @return RepositoryPaginatedCollection
     */
    public function getRepositoryPaginatedCollectionByName(Project $project, $repository_name, $limit, $offset)
    {
        $repositories = [];
        foreach ($this->dao->searchPaginatedByProjectAndByName($project, $repository_name, $limit, $offset) as $row) {
            $repositories[] = $this->instantiateFromRow($row, $project);
        }

        return new RepositoryPaginatedCollection(
            $repositories,
            $this->dao->foundRows()
        );
    }

    /**
     * @return RepositoryWithLastCommitDate[]
     */
    public function getRepositoriesInProjectWithLastCommitInfo(Project $project): array
    {
        $repositories = [];
        foreach ($this->dao->searchByProject($project) as $row) {
            $repositories[] = new RepositoryWithLastCommitDate(
                $this->instantiateFromRow($row, $project),
                $row['commit_date'] === null || $row['commit_date'] === '' ? null : new \DateTimeImmutable('@' . $row['commit_date']),
            );
        }

        return $repositories;
    }

    public function getRepositoryByName(Project $project, string $name): Repository
    {
        $row = $this->dao->searchRepositoryByName($project, $name);
        if ($row) {
            return $this->instantiateFromRow($row, $project);
        } else {
            throw new CannotFindRepositoryException();
        }
    }

    public function getByIdAndProject(int $id_repository, Project $project): Repository
    {
        $row = $this->dao->searchByRepositoryIdAndProjectId($id_repository, $project);
        if (! $row) {
            throw new CannotFindRepositoryException();
        }

        return $this->instantiateFromRow($row, $project);
    }

    public function getRepositoryById(int $id): Repository
    {
        $row = $this->dao->searchByRepositoryId($id);
        if (! $row) {
            throw new CannotFindRepositoryException();
        }

        return $this->instantiateFromRowWithoutProject($row);
    }

    public function getCoreRepository(\Project $project): Repository
    {
        $repository_id = $this->dao->getCoreRepositoryId($project);
        if ($repository_id === null) {
            throw new CannotFindRepositoryException();
        }

        return CoreRepository::buildActiveRepository($project, $repository_id);
    }

    public function queueRepositoryRestore(Repository $repository, SystemEventManager $system_event_manager): ?SystemEvent
    {
        return $system_event_manager->createEvent(
            'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_RESTORE_REPOSITORY::NAME,
            $repository->getProject()->getID() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId(),
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_ROOT
        );
    }

    /**
     * @throws CannotFindRepositoryException
     */
    public function getRepositoryFromSystemPath(string $path): Repository
    {
        $real_path = realpath($path);
        if ($this->isPathInSvnCore($real_path)) {
            return $this->getCoreRepositoryFromSystemPath($real_path);
        }

        if (! preg_match('/\/(\d+)\/(' . RuleName::PATTERN_REPOSITORY_NAME . ')$/', $path, $matches)) {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }

        $project = $this->project_manager->getProject($matches[1]);
        return $this->getRepositoryIfProjectIsValid($project, $matches[2]);
    }

    private function isPathInSvnCore(string $path): bool
    {
        $core_svnroot = ForgeConfig::get('svn_prefix');
        if ($core_svnroot === false) {
            return false;
        }

        $core_svnroot_path = realpath($core_svnroot);
        if ($core_svnroot_path === false || strpos($path, $core_svnroot_path) !== 0) {
            return false;
        }

        return true;
    }

    private function getCoreRepositoryFromSystemPath(string $path): Repository
    {
        $project_name = basename($path);
        $project      = $this->project_manager->getProjectByCaseInsensitiveUnixName($project_name);
        if (! $project instanceof Project || $project->getID() === null || $project->isError() || ! $project->isActive()) {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }
        $core_repository_id = $this->dao->getCoreRepositoryId($project);
        if ($core_repository_id === null) {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Core repository cannot be managed by plugin'));
        }
        return CoreRepository::buildActiveRepository($project, $core_repository_id);
    }

    /**
     * @throws CannotFindRepositoryException
     */
    public function getRepositoryFromPublicPath(HTTPRequest $request): Repository
    {
        $path         = $request->get('root');
        $project      = $request->getProject();
        $project_name = $project->getUnixNameMixedCase();

        $matches = [];
        if (preg_match('/^' . preg_quote($project_name, '/') . '$/', $path, $matches)) {
            $repository_id = $this->dao->getCoreRepositoryId($project);
            if ($repository_id === null) {
                throw new CannotFindRepositoryException();
            }
            return CoreRepository::buildActiveRepository($project, $repository_id);
        }

        $matches = [];
        if (! preg_match('/^' . preg_quote($project_name, '/') . '\/(' . RuleName::PATTERN_REPOSITORY_NAME . ')$/', $path, $matches)) {
            throw new CannotFindRepositoryException();
        }

        return $this->getRepositoryIfProjectIsValid($project, $matches[1]);
    }

    /**
     * @param $project
     * @param $repository_name
     *
     * @return Repository
     * @throws CannotFindRepositoryException
     */
    private function getRepositoryIfProjectIsValid(Project $project, $repository_name)
    {
        if (! $project instanceof Project || $project->getID() == null || $project->isError()) {
            throw new CannotFindRepositoryException(dgettext('tuleap-svn', 'Repository not found'));
        }

        return $this->getRepositoryByName($project, $repository_name);
    }

    private function getArchivedRepositoriesToPurge($retention_date)
    {
        $deleted_repositories = $this->dao->getDeletedRepositoriesToPurge($retention_date);
        return $this->instantiateRepositoriesFromRow($deleted_repositories);
    }

    /**
     * @return RestorableRepositoryPresenter[]
     */
    public function getRestorableRepositoriesByProject(Project $project, \PFUser $user): array
    {
        $deleted_repositories              = $this->dao->getRestorableRepositoriesByProject($project->getID());
        $deleted_repositories_instantiated = $this->instantiateRepositoriesFromRow($deleted_repositories);
        $deleted_existed_repositories      = [];
        foreach ($deleted_repositories_instantiated as $delete_repository) {
            $archive = $delete_repository->getBackupPath();
            if ($archive && file_exists($archive)) {
                $repository_presenter = new RestorableRepositoryPresenter(
                    $user,
                    $delete_repository->getName(),
                    (int) $delete_repository->getDeletionDate(),
                    $project->getID(),
                    $delete_repository->getId()
                );
                array_push($deleted_existed_repositories, $repository_presenter);
            }
        }
        return $deleted_existed_repositories;
    }

    /**
     * @return Repository[]
     */
    private function instantiateRepositoriesFromRow($deleted_repositories): array
    {
        $archived_repositories = [];
        foreach ($deleted_repositories as $deleted_repository) {
            $repository = $this->instantiateFromRowWithoutProject($deleted_repository);
            array_push($archived_repositories, $repository);
        }
        return $archived_repositories;
    }

    private function instantiateFromRow(array $row, Project $project): Repository
    {
        if ((int) $row['is_core'] === 1) {
            return CoreRepository::buildActiveRepository($project, (int) $row['id']);
        }
        return SvnRepository::buildFromDatabase($row, $project);
    }

    private function instantiateFromRowWithoutProject(array $row): Repository
    {
        $project = $this->project_manager->getProject($row['project_id']);

        return $this->instantiateFromRow($row, $project);
    }

    public function purgeArchivedRepositories()
    {
        if (! ForgeConfig::exists('sys_file_deletion_delay')) {
            $this->logger->warning("Purge of archived SVN repositories is disabled: sys_file_deletion_delay is missing in local.inc file");
            return;
        }
        $retention_period      = intval(ForgeConfig::get('sys_file_deletion_delay'));
        $retention_date        = strtotime("-" . $retention_period . " days");
        $archived_repositories = $this->getArchivedRepositoriesToPurge($retention_date);

        foreach ($archived_repositories as $repository) {
            try {
                if ($this->archiveBeforePurge($repository)) {
                    $this->deleteArchivedRepository($repository);
                    $this->destructor->delete($repository);
                }
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    public function restoreRepository(Repository $repository)
    {
        $this->logger->info('Restoring repository : ' . $repository->getName());
        $backup_path = $repository->getBackupPath();

        if (! $backup_path || ! file_exists($backup_path)) {
            $this->logger->error('[Restore] Unable to find repository archive: ' . $backup_path);
            return false;
        }

        $this->svnadmin->importRepository($repository);

        $new_ugroup_name = null;
        $old_ugroup_name = null;
        $this->backend->updateCustomSVNAccessForRepository(
            $repository->getProject(),
            $repository->getSystemPath(),
            $new_ugroup_name,
            $old_ugroup_name,
            $repository->getFullName(),
            $this->access_file_history_factory->getCurrentVersion($repository)->getContent()
        );
        $this->deleteArchivedRepository($repository);
        $this->dao->markAsDeleted($repository->getId(), null, null);
    }

    private function deleteArchivedRepository(Repository $repository)
    {
        $this->logger->info('Purge of archived SVN repository: ' . $repository->getName());
        $path = $repository->getBackupPath();
        $this->logger->debug('Delete backup ' . $path);
        if (! $path || ! is_writable($path)) {
            $this->logger->debug('Empty path or permission denied ' . $path);
            return;
        }
        $this->logger->debug('Removing physically the repository');

        $command_output = $this->system_command->exec('rm -rf ' . escapeshellarg($path));
        foreach ($command_output as $line) {
            $this->logger->debug('[svn ' . $repository->getName() . '] cannot remove repository: ' . $line);
        }
    }

    private function archiveBeforePurge(Repository $repository): bool
    {
        $source_path = $repository->getBackupPath();

        if ($source_path && dirname($source_path)) {
            $event = new ArchiveDeletedItemEvent(new ArchiveDeletedItemFileProvider($source_path, self::PREFIX));

            $this->event_manager->processEvent($event);

            if ($event->isSuccessful()) {
                $this->logger->info('The repository' . $repository->getName() . ' has been moved to the archiving area before purge ');
                $this->logger->info('Archive of the SVN repository: ' . $repository->getName() . ' done');
                return true;
            }

            $this->logger->warning('Can not move the repository ' . $repository->getName() . ' to the archiving area before purge.');
            $this->logger->warning('An error occured while archiving SVN repository: ' . $repository->getName());
            return false;
        }

        return false;
    }

    /**
     * @return RepositoryByProjectCollection[]
     */
    public function getRepositoriesOfNonDeletedProjects(): array
    {
        $repository_list = $this->dao->searchRepositoriesOfNonDeletedProjects();

        $repository_by_projects = [];
        foreach ($repository_list as $repository) {
            $repository_by_projects[$repository['project_id']][] = $this->instantiateFromRowWithoutProject($repository);
        }

        $collection = [];
        foreach ($repository_by_projects as $project_id => $project_repositories) {
            $project      = $this->project_manager->getProject($project_id);
            $collection[] = RepositoryByProjectCollection::build($project, $project_repositories);
        }

        return $collection;
    }

    /**
     * @return \Generator<int, Repository>
     */
    public function getAllRepositoriesInActiveProjects(): \Generator
    {
        $dar = $this->dao->getAllRepositoriesInActiveProjects();
        if (! $dar || $dar->isError()) {
            throw new \RuntimeException('Database error');
        }
        foreach ($dar as $row) {
            yield $this->instantiateFromRow(
                $row,
                $this->project_manager->getProjectFromDbRow($row)
            );
        }
    }

    /**
     * @throws \DataAccessQueryException
     */
    public function svnCoreUsage(SvnCoreUsage $svn_core_usage): void
    {
        foreach ($this->dao->getCoreRepositories() as $project_id) {
            $svn_core_usage->addProjectId($project_id);
        }
    }
}
