<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ForumML;

use HTTPRequest;
use Project;
use System_Command;
use Tuleap\MailingList\ServiceMailingList;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ListInfoFromVariablesProvider
{
    /**
     * @var ThreadsDao
     */
    private $dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \ForumMLPlugin
     */
    private $plugin;
    /**
     * @var System_Command
     */
    private $command;

    public function __construct(
        \ForumMLPlugin $plugin,
        \ProjectManager $project_manager,
        ThreadsDao $dao,
        System_Command $command,
    ) {
        $this->plugin          = $plugin;
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
        $this->command         = $command;
    }

    public function getProject(array $variables): Project
    {
        $row = $this->dao->searchActiveList((int) $variables['id']);
        if (! $row) {
            throw new NotFoundException();
        }

        return $this->getProjectFromListRow($row['group_id']);
    }

    public function getListInfoFromVariables(HTTPRequest $request, array $variables): ListInfoFromVariables
    {
        $list_id = (int) $variables['id'];

        $row = $this->dao->searchActiveList($list_id);
        if (! $row) {
            throw new NotFoundException();
        }

        $list_name = $row['list_name'];

        $project = $this->getProjectFromListRow($row['group_id']);
        $service = $project->getService(\Service::ML);
        if (! $service instanceof ServiceMailingList) {
            throw new ForbiddenException();
        }

        if (! $this->plugin->isAllowed((int) $project->getID())) {
            throw new ForbiddenException();
        }

        $user = $request->getCurrentUser();
        if (! $this->canUserAccessToList($user, $project, $row['is_public'], $list_name)) {
            throw new ForbiddenException(
                dgettext('tuleap-forumml', 'You are not allowed to access the archives of this list')
            );
        }

        return new ListInfoFromVariables($list_id, $list_name, $row, $project, $service);
    }

    private function getProjectFromListRow(int $group_id): Project
    {
        return $this->project_manager->getProject($group_id);
    }

    private function canUserAccessToList(\PFUser $user, Project $project, int $is_public, string $list_name): bool
    {
        if ($is_public === 1) {
            return true;
        }

        if (! $user->isLoggedIn()) {
            return false;
        }

        if (! $user->isMember((int) $project->getID())) {
            return false;
        }

        $members = $this->command->exec(
            \ForgeConfig::get('mailman_bin_dir') . "/list_members " . escapeshellarg($list_name)
        );

        return in_array($user->getEmail(), $members, true);
    }
}
