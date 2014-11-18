<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class Git_Mirror_MirrorDataMapper {

    const MIRROR_OWNER_PREFIX = 'forge__gitmirror_';

    /** Git_Mirror_MirrorDao */
    private $dao;

    /** UserManager */
    private $user_manager;

    public function __construct(Git_Mirror_MirrorDao $dao, UserManager $user_manager) {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
    }

    /**
     * @return Git_Mirror_Mirror
     * @throws Git_Mirror_MissingDataException
     * @throws Git_Mirror_CreateException
     */
    public function save($url, $ssh_key, $password, $name) {
        if (! $url || ! $ssh_key || ! $name) {
            throw new Git_Mirror_MissingDataException();
        }

        $mirror_id = $this->dao->save($url, $name);
        if (! $mirror_id) {
            throw new Git_Mirror_CreateException();
        }

        $user = $this->createUserForMirror($mirror_id, $password, $ssh_key);

        return $this->getInstanceFromRow($user, array(
            'id'   => $mirror_id,
            'url'  => $url,
            'name' => $name
        ));
    }

    private function createUserForMirror($mirror_id, $password, $ssh_key) {
        $user = new PFUser(array(
            'user_name'       => self::MIRROR_OWNER_PREFIX.$mirror_id,
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
    public function fetchAll() {
        $rows = $this->dao->fetchAll();

        $mirrors = array();
        foreach ($rows as $row) {
            $owner     = $this->getMirrorOwner($row['id']);
            $mirrors[] = $this->getInstanceFromRow($owner, $row);
        }

        return $mirrors;
    }

    /**
     * @return Git_Mirror_Mirror[]
     */
    public function fetchAllRepositoryMirrors(GitRepository $repository) {
        if ($repository instanceof GitRepositoryGitoliteAdmin) {
            return $this->fetchAll();
        }

        $rows = $this->dao->fetchAllRepositoryMirrors($repository->getId());

        $mirrors = array();
        foreach ($rows as $row) {
            $owner     = $this->getMirrorOwner($row['id']);
            $mirrors[] = $this->getInstanceFromRow($owner, $row);
        }

        return $mirrors;
    }

    public function fetchRepositoriesForMirror(Git_Mirror_Mirror $mirror) {
        $factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $repositories = array();
        foreach ($this->dao->fetchAllRepositoryMirroredByMirror($mirror->id) as $row) {
            $repositories[] = $factory->instanciateFromRow($row);
        }

        return $repositories;
    }

    public function fetchRepositoriesPerMirrorPresenters(Git_Mirror_Mirror $mirror) {
        $presenters = array();

        $previous_group_id = -1;
        foreach ($this->dao->fetchAllRepositoryMirroredByMirror($mirror->id) as $row) {
            if ($previous_group_id !== $row['group_id']) {
                $project_presenter = new Git_AdminRepositoryListForProjectPresenter($row['group_id'], $row['group_name']);
                $presenters[]      = $project_presenter;
            }

            $project_presenter->repositories[] = array(
                'repository_id'   => $row['repository_id'],
                'repository_path' => $row['repository_path'],
            );

            $previous_group_id = $row['group_id'];
        }

        return $presenters;
    }

    public function doesAllSelectedMirrorIdsExist($selected_mirror_ids) {
        if ($selected_mirror_ids !== false) {
            return count($selected_mirror_ids) === count($this->dao->fetchByIds($selected_mirror_ids));
        }
        return true;
    }

    public function unmirrorRepository($repository_id) {
        return $this->dao->unmirrorRepository($repository_id);
    }

    public function mirrorRepositoryTo($repository_id, $selected_mirror_ids) {
        if ($selected_mirror_ids !== false) {
            return $this->dao->mirrorRepositoryTo($repository_id, $selected_mirror_ids);
        }
        return true;
    }

    /**
     * @return bool
     * @throws Git_Mirror_MirrorNoChangesException
     * @throws Git_Mirror_MirrorNotFoundException
     * @throws Git_Mirror_MissingDataException
     */
    public function update($id, $url, $ssh_key, $name) {
        $mirror = $this->fetch($id);

        if ($url == $mirror->url && $ssh_key == $mirror->ssh_key && $name == $mirror->name) {
            throw new Git_Mirror_MirrorNoChangesException();
        }

        if (! $url || ! $ssh_key) {
            throw new Git_Mirror_MissingDataException();
        }

        if ($ssh_key != $mirror->ssh_key) {
            $this->user_manager->updateUserSSHKeys($mirror->owner, array($ssh_key));
        }

        return $this->dao->updateMirror($id, $url, $name);
    }

    /**
     * @return bool
     * @throws Git_Mirror_MirrorNotFoundException
     */
    public function delete($id) {
        $mirror = $this->fetch($id);

        if (! $this->dao->delete($id)) {
            return false;
        }

        $user = $this->user_manager->getUserById($mirror->owner_id);
        $user->setStatus(PFUser::STATUS_DELETED);
        $this->user_manager->updateDb($user);

        return true;
    }

    /**
     * @return Git_Mirror_Mirror
     * @throws Git_Mirror_MirrorNotFoundException
     */
    public function fetch($id) {
        $row = $this->dao->fetch($id);
        if (! $row) {
            throw new Git_Mirror_MirrorNotFoundException();
        }
        $owner = $this->getMirrorOwner($row['id']);

        return $this->getInstanceFromRow($owner, $row);
    }

    /**
     * @return Git_Mirror_Mirror
     */
    private function getInstanceFromRow(PFUser $owner, $row) {
        return new Git_Mirror_Mirror(
            $owner,
            $row['id'],
            $row['url'],
            $row['name']
        );
    }

    /**
     * @return PFUser
     */
    private function getMirrorOwner($mirror_id) {
        return $this->user_manager->getUserByUserName(
            self::MIRROR_OWNER_PREFIX.$mirror_id
        );
    }
}
