<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
require_once 'GenericUser.class.php';
require_once 'GenericUserAlreadyExistsException.class.php';
require_once 'UserManager.class.php';
require_once 'common/dao/GenericUserDao.class.php';


class GenericUserFactory {

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     *
     * @var GenericUserDao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(UserManager $userManager, ProjectManager $project_manager, GenericUserDao $dao) {
        $this->user_manager = $userManager;
        $this->project_manager = $project_manager;
        $this->dao = $dao;
    }

    /**-
     * @param int $group_id
     * @param string $password
     * @return GenericUser
     */
    public function create($group_id, $password) {
        $project = $this->project_manager->getProject($group_id);
        
        $user = new GenericUser($project);
        $user->setPassword($password);

        return $user;
    }

    /**
     *
     * @param int $group_id
     * @param int $password
     * @return PFUser
     */
    public function save(GenericUser $user) {
        $group_id = $user->getProject()->getID();

        if ($this->fetch($group_id)->getRow()) {
            throw new GenericUserAlreadyExistsException('Generic User already exists in this project');
        }
        
        $user = $this->user_manager->createAccount($user);
        $this->getDao()->save($group_id, $user->getId());

        return $user;
    }

    private function fetch($group_id) {
        return $this->getDao()->fetch($group_id);
    }

    private function getDao() {
        return $this->dao;
    }
}
?>
