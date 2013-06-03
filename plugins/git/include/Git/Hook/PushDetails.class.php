<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * Store informations about a push
 */
class Git_Hook_PushDetails {
    const CREATE_BRANCH = 'create_branch';
    const DELETE_BRANCH = 'delete_branch';
    const UPDATE_BRANCH = 'update_branch';

    private $type;
    private $revision_list;
    private $repository;
    private $user;

    public function __construct(GitRepository $repository, PFUser $user, $refname, $type, array $revision_list) {
        $this->repository = $repository;
        $this->user       = $user;
        $this->refname    = $refname;
        $this->type       = $type;
        $this->revision_list = $revision_list;
    }

    public function getRepository() {
        return $this->repository;
    }

    public function getUser() {
        return $this->user;
    }

    public function getRefname() {
        return $this->refname;
    }

    public function getType() {
        return $this->type;
    }

    public function getRevisionList() {
        return $this->revision_list;
    }
}

?>
