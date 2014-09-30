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
    public function save($url, $ssh_key, $password) {
        if (! $url || ! $ssh_key) {
            throw new Git_Mirror_MissingDataException();
        }

        $mirror_id = $this->dao->save($url, $ssh_key);
        if (! $mirror_id) {
            throw new Git_Mirror_CreateException();
        }

        $user = $this->createUserForMirror($mirror_id, $password);

        return $this->getInstanceFromRow($user, array(
            'id'      => $mirror_id,
            'url'     => $url,
            'ssh_key' => $ssh_key
        ));
    }

    private function createUserForMirror($mirror_id, $password) {
        $user = new PFUser(array(
            'user_name' => self::MIRROR_OWNER_PREFIX.$mirror_id,
        ));
        $user->setPassword($password);
        $this->user_manager->createAccount($user);

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
     * @return bool
     * @throws Git_Mirror_MirrorNoChangesException
     * @throws Git_Mirror_MirrorNotFoundException
     * @throws Git_Mirror_MissingDataException
     */
    public function update($id, $url, $ssh_key) {
        $mirror = $this->fetch($id);

        if ($url == $mirror->url && $ssh_key == $mirror->ssh_key) {
            throw new Git_Mirror_MirrorNoChangesException();
        }

        if (! $url || ! $ssh_key) {
            throw new Git_Mirror_MissingDataException();
        }

        return $this->dao->updateMirror($id, $url, $ssh_key);
    }


    /**
     * @return Git_Mirror_Mirror
     * @throws Git_Mirror_MirrorNotFoundException
     */
    private function fetch($id) {
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
            $row['ssh_key']
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
