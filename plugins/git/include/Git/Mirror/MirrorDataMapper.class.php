<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Git_Mirror_MirrorDataMapper
{

    public const MIRROR_OWNER_PREFIX = 'forge__gitmirror_';
    public const PROJECTS_HOSTNAME   = 'projects';

    /** @var Git_Mirror_MirrorDao */
    private $dao;

    /** UserManager */
    private $user_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    /** @var Git_Gitolite_GitoliteRCReader */
    private $reader;

    /**
     * @var DefaultProjectMirrorDao
     */
    private $default_dao;

    public function __construct(
        Git_Mirror_MirrorDao $dao,
        UserManager $user_manager,
        GitRepositoryFactory $repository_factory,
        ProjectManager $project_manager,
        Git_SystemEventManager $git_system_event_manager,
        Git_Gitolite_GitoliteRCReader $reader,
        DefaultProjectMirrorDao $default_dao
    ) {
        $this->dao                      = $dao;
        $this->user_manager             = $user_manager;
        $this->repository_factory       = $repository_factory;
        $this->project_manager          = $project_manager;
        $this->git_system_event_manager = $git_system_event_manager;
        $this->reader                   = $reader;
        $this->default_dao              = $default_dao;
    }

    /**
     * @return Git_Mirror_Mirror
     * @throws Git_Mirror_MissingDataException
     * @throws Git_Mirror_CreateException
     */
    public function save($url, $hostname, $ssh_key, $password, $name)
    {
        if (! $url || ! $ssh_key || ! $name) {
            throw new Git_Mirror_MissingDataException();
        }

        $this->checkThatHostnameIsValidOnCreation($hostname);

        $mirror_id = $this->dao->save($url, $hostname, $name);
        if (! $mirror_id) {
            throw new Git_Mirror_CreateException();
        }

        $user = $this->createUserForMirror($mirror_id, $password, $ssh_key);

        return $this->getInstanceFromRow($user, array(
            'id'       => $mirror_id,
            'url'      => $url,
            'hostname' => $hostname,
            'name'     => $name
        ));
    }

    private function checkThatHostnameIsValidOnCreation($hostname)
    {
        if (! $hostname) {
            return true;
        }

        if ($this->dao->getNumberOfMirrorByHostname($hostname) > 0) {
            throw new Git_Mirror_HostnameAlreadyUsedException();
        }

        if ($this->doesHostnameIsForbidden($hostname)) {
            throw new Git_Mirror_HostnameIsReservedException();
        }

        return true;
    }

    private function doesHostnameIsForbidden($hostname)
    {
        return strtolower($hostname) === self::PROJECTS_HOSTNAME ||
               strtolower($hostname) === strtolower($this->reader->getHostname());
    }

    private function checkThatHostnameIsValidOnUpdate($id, $hostname)
    {
        if (! $hostname) {
            return true;
        }

        if ($this->dao->getNumberOfMirrorByHostnameExcludingGivenId($hostname, $id) > 0) {
            throw new Git_Mirror_HostnameAlreadyUsedException();
        }

        if ($this->doesHostnameIsForbidden($hostname)) {
            throw new Git_Mirror_HostnameIsReservedException();
        }

        return true;
    }

    private function createUserForMirror($mirror_id, $password, $ssh_key)
    {
        $user = new PFUser(array(
            'user_name'       => self::MIRROR_OWNER_PREFIX . $mirror_id,
            'status'          => 'A',
            'unix_status'     => 'A'
        ));
        $user->setPassword($password);
        $this->user_manager->createAccount($user);
        $this->user_manager->addSSHKeys($user, $ssh_key);

        return $user;
    }

    /**
     * @return Git_Mirror_Mirror[]
     */
    public function fetchAll()
    {
        $rows = $this->dao->fetchAll();

        return $this->mapDataAccessResultToArrayOfMirrors($rows);
    }

    /**
     * @return Git_Mirror_Mirror[]
     */
    public function fetchAllForProject(Project $project)
    {
        $rows = $this->dao->fetchAllForProject($project->getID());

        return $this->mapDataAccessResultToArrayOfMirrors($rows);
    }

    /**
     * @return Git_Mirror_Mirror[]
     */
    public function fetchAllRepositoryMirrors(GitRepository $repository)
    {
        if ($repository instanceof GitRepositoryGitoliteAdmin) {
            return $this->fetchAll();
        }

        $rows = $this->dao->fetchAllRepositoryMirrors($repository->getId());

        return $this->mapDataAccessResultToArrayOfMirrors($rows);
    }

    /**
     * @return Git_Mirror_Mirror[]
     */
    private function mapDataAccessResultToArrayOfMirrors(array $rows)
    {
        $mirrors = array();
        foreach ($rows as $row) {
            $owner     = $this->getMirrorOwner($row['id']);
            $mirrors[] = $this->getInstanceFromRow($owner, $row);
        }

        return $mirrors;
    }

    public function fetchRepositoriesForMirror(Git_Mirror_Mirror $mirror)
    {
        $repositories = array();
        foreach ($this->dao->fetchAllRepositoryMirroredByMirror($mirror->id) as $row) {
            $repositories[] = $this->repository_factory->instanciateFromRow($row);
        }

        return $repositories;
    }

    public function fetchAllProjectRepositoriesForMirror(Git_Mirror_Mirror $mirror, array $project_ids)
    {
        $rows         = $this->dao->fetchAllProjectRepositoriesForMirror($mirror->id, $project_ids);
        $repositories = array();

        foreach ($rows as $row) {
            $repositories[] = $this->repository_factory->instanciateFromRow($row);
        }

        return $repositories;
    }

    public function fetchRepositoriesPerMirrorPresenters(Git_Mirror_Mirror $mirror)
    {
        $presenters = array();

        $previous_group_id = -1;
        foreach ($this->dao->fetchAllRepositoryMirroredByMirror($mirror->id) as $row) {
            if ($previous_group_id !== $row['group_id']) {
                $project_presenter = new Git_AdminRepositoryListForProjectPresenter(
                    $row['group_id'],
                    $row['group_name']
                );
                $presenters[] = $project_presenter;
            }

            $project_presenter->repositories[] = array(
                'repository_id'   => $row['repository_id'],
                'repository_path' => $row['repository_path'],
            );

            $previous_group_id = $row['group_id'];
        }

        return $presenters;
    }

    /**
     * @return Project[]
     */
    public function fetchAllProjectsConcernedByMirroring()
    {
        $projects = array();

        foreach ($this->dao->fetchAllProjectIdsConcernedByMirroring() as $row) {
            $projects[] = $this->project_manager->getProject($row['project_id']);
        }

        return $projects;
    }

    public function fetchAllProjectsConcernedByAMirror(Git_Mirror_Mirror $mirror)
    {
        $projects = array();

        foreach ($this->dao->fetchAllProjectIdsConcernedByAMirror($mirror->id) as $row) {
            $projects[] = $this->project_manager->getProject($row['project_id']);
        }

        return $projects;
    }

    public function doesAllSelectedMirrorIdsExist($selected_mirror_ids)
    {
        if ($selected_mirror_ids) {
            return count($selected_mirror_ids) === count($this->dao->fetchByIds($selected_mirror_ids));
        }
        return true;
    }

    public function unmirrorRepository($repository_id)
    {
        return $this->dao->unmirrorRepository($repository_id);
    }

    public function mirrorRepositoryTo($repository_id, $selected_mirror_ids)
    {
        if ($selected_mirror_ids) {
            return $this->dao->mirrorRepositoryTo($repository_id, $selected_mirror_ids);
        }
        return true;
    }

    public function removeAllDefaultMirrorsToProject(Project $project)
    {
        return $this->default_dao->removeAllToProject($project->getID());
    }

    public function addDefaultMirrorsToProject(Project $project, array $selected_mirror_ids)
    {
        if ($selected_mirror_ids) {
            return $this->default_dao->addDefaultMirrorsToProject($project->getID(), $selected_mirror_ids);
        }

        return true;
    }

    public function getDefaultMirrorIdsForProject(Project $project)
    {
        return $this->default_dao->getDefaultMirrorIdsForProject($project->getID());
    }

    /**
     * @return bool
     * @throws Git_Mirror_MirrorNoChangesException
     * @throws Git_Mirror_MirrorNotFoundException
     * @throws Git_Mirror_MissingDataException
     */
    public function update($id, $url, $hostname, $ssh_key, $name)
    {
        $mirror = $this->fetch($id);

        if ($url == $mirror->url && $hostname == $mirror->hostname && $ssh_key == $mirror->ssh_key && $name == $mirror->name) {
            throw new Git_Mirror_MirrorNoChangesException();
        }

        $this->checkThatHostnameIsValidOnUpdate($id, $hostname);

        if (! $url || ! $ssh_key) {
            throw new Git_Mirror_MissingDataException();
        }

        if ($ssh_key != $mirror->ssh_key) {
            $this->user_manager->updateUserSSHKeys($mirror->owner, array($ssh_key));
        }

        $this->git_system_event_manager->queueUpdateMirror($id, $mirror->hostname);

        return $this->dao->updateMirror($id, $url, $hostname, $name);
    }

    /**
     * @return bool
     * @throws Git_Mirror_MirrorNotFoundException
     */
    public function delete($id)
    {
        $mirror = $this->fetch($id);

        if (! $this->dao->delete($id)) {
            return false;
        }

        $user = $this->user_manager->getUserById($mirror->owner_id);
        if ($user === null) {
            return false;
        }
        $user->setStatus(PFUser::STATUS_DELETED);
        $this->user_manager->updateDb($user);
        $this->git_system_event_manager->queueDeleteMirror($id, $mirror->hostname);

        return true;
    }

    /**
     * @return bool
     */
    public function deleteFromDefaultMirrors($deleted_mirror_id)
    {
        return $this->default_dao->deleteFromDefaultMirrors($deleted_mirror_id);
    }

    public function deleteFromDefaultMirrorsInProjects(Git_Mirror_Mirror $mirror, array $project_ids)
    {
        return $this->default_dao->deleteFromDefaultMirrorsInProjects($mirror->id, $project_ids);
    }

    /**
     * @return Git_Mirror_Mirror
     * @throws Git_Mirror_MirrorNotFoundException
     */
    public function fetch($id)
    {
        $row = $this->dao->fetch($id);
        if (! $row) {
            throw new Git_Mirror_MirrorNotFoundException();
        }
        $owner = $this->getMirrorOwner($row['id']);

        return $this->getInstanceFromRow($owner, $row);
    }

    public function getListOfMirrorIdsPerRepositoryForProject(Project $project)
    {
        $repositories = array();
        foreach ($this->dao->fetchAllRepositoryMirroredInProject($project->getId()) as $row) {
            if (! isset($repositories[$row['repository_id']])) {
                $repositories[$row['repository_id']] = array();
            }

            $repositories[$row['repository_id']][] = $row['mirror_id'];
        }

        return $repositories;
    }

    /**
     * @return Git_Mirror_Mirror
     */
    private function getInstanceFromRow(PFUser $owner, $row)
    {
        return new Git_Mirror_Mirror(
            $owner,
            $row['id'],
            $row['url'],
            $row['hostname'],
            $row['name']
        );
    }

    /**
     * @return PFUser
     */
    private function getMirrorOwner($mirror_id)
    {
        return $this->user_manager->getUserByUserName(
            self::MIRROR_OWNER_PREFIX . $mirror_id
        );
    }

    /**
     * @return bool
     */
    public function duplicate($template_project_id, $new_project_id)
    {
        return $this->default_dao->duplicate($template_project_id, $new_project_id);
    }
}
