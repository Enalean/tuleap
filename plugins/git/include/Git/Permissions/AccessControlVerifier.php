<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use GitRepository;
use PFUser;
use System_Command;
use Tuleap\Git\PathJoinUtil;

class AccessControlVerifier
{
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;
    /**
     * @var System_Command
     */
    private $system_command;

    public function __construct(FineGrainedRetriever $fine_grained_retriever, System_Command $system_command)
    {
        $this->fine_grained_retriever = $fine_grained_retriever;
        $this->system_command         = $system_command;
    }

    /**
     * @return bool
     */
    public function canWrite(PFUser $user, GitRepository $repository, $reference)
    {
        if ($this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)) {
            if ($reference === '') {
                return false;
            }
            return $this->canWriteAccordingToGitolite($user, $repository, $reference);
        }

        return $user->hasPermission(\Git::PERM_WRITE, $repository->getId(), $repository->getProjectId()) ||
            $user->hasPermission(\Git::PERM_WPLUS, $repository->getId(), $repository->getProjectId());
    }

    /**
     * @return bool
     */
    private function canWriteAccordingToGitolite(PFUser $user, GitRepository $repository, $reference)
    {
        $repository_name = escapeshellarg(PathJoinUtil::unixPathJoin(array($repository->getProject()->getUnixName(), $repository->getFullName())));
        $user_name       = escapeshellarg($user->getUserName());
        $reference       = escapeshellarg($reference);

        try {
            $this->system_command->exec("sudo -u gitolite gitolite access -q $repository_name $user_name 'W' $reference");
        } catch (\System_Command_CommandException $ex) {
            return false;
        }

        return true;
    }
}
