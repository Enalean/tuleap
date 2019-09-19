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


class GenericUserFactory
{
    public const CONFIG_KEY_SUFFIX = 'sys_generic_user_suffix';

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

    public function __construct(UserManager $userManager, ProjectManager $project_manager, GenericUserDao $dao)
    {
        $this->user_manager = $userManager;
        $this->project_manager = $project_manager;
        $this->dao = $dao;
    }

    /**
     * @return GenericUser
     */
    public function update(GenericUser $user)
    {
        $this->user_manager->updateDb($user);

        return $user;
    }

    /**
     *
     * @param int $group_id
     * @return GenericUser|null
     */
    public function fetch($group_id)
    {
        if ($row = $this->dao->fetch($group_id)->getRow()) {
            $pfuser = $this->user_manager->getUserById($row['user_id']);

            $generic_user = $this->generateGenericUser($group_id, $pfuser);

            return $generic_user;
        }

        return null;
    }

    /**
     *
     * @param int $group_id
     * @param string $password
     * @return GenericUser
     */
    public function create($group_id, $password)
    {
        $generic_user = $this->generateGenericUser($group_id, new PFUser());
        $generic_user->setPassword($password);

        $this->user_manager->createAccount($generic_user);
        $this->dao->save($group_id, $generic_user->getId());

        return $generic_user;
    }

    /**-
     * @param int $group_id
     * @return GenericUser
     */
    private function generateGenericUser($group_id, PFUser $user)
    {
        $project = $this->project_manager->getProject($group_id);
        return $this->getGenericUser($project, $user);
    }

    public function getGenericUser(Project $project, PFUser $user)
    {
        return new GenericUser($project, $user, ForgeConfig::get(self::CONFIG_KEY_SUFFIX));
    }
}
