<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1\Branch;

use Git_Command_Exception;
use Luracast\Restler\RestException;
use Tuleap\Git\Branch\BranchCreationExecutor;
use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Branch\CannotCreateNewBranchException;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\REST\v1\GitBranchPOSTRepresentation;

class BranchCreator
{
    private const BRANCH_PREFIX = "refs/heads/";

    public function __construct(
        private \Git_Exec $git_exec,
        private BranchCreationExecutor $branch_creation_executor,
        private AccessControlVerifier $access_control_verifier,
    ) {
    }

    /**
     * @throws RestException
     */
    public function createBranch(\PFUser $user, \GitRepository $repository, GitBranchPOSTRepresentation $representation): void
    {
        try {
            BranchName::fromBranchNameShortHand($representation->branch_name);
        } catch (InvalidBranchNameException $exception) {
            throw new RestException(
                400,
                sprintf(
                    "The branch name %s is not a valid branch name",
                    $representation->branch_name
                )
            );
        }

        if (! $this->access_control_verifier->canWrite($user, $repository, self::BRANCH_PREFIX . $representation->branch_name)) {
            throw new RestException(
                403,
                sprintf(
                    "You are not allowed to create the branch %s in repository %s",
                    $representation->branch_name,
                    $repository->getName()
                )
            );
        }

        $all_branches_names = $this->git_exec->getAllBranchesSortedByCreationDate();
        if (in_array($representation->branch_name, $all_branches_names)) {
            throw new RestException(
                400,
                sprintf(
                    "The branch %s already exists in the repository %s",
                    $representation->branch_name,
                    $repository->getName()
                )
            );
        }

        try {
            $object_type = $this->git_exec->getObjectType($representation->reference);
        } catch (Git_Command_Exception $exception) {
            throw new RestException(
                400,
                sprintf(
                    "The object %s does not exist in the repository %s",
                    $representation->reference,
                    $repository->getName()
                )
            );
        }

        if (
            ! in_array($representation->reference, $all_branches_names)
            && $object_type !== 'commit'
        ) {
            throw new RestException(
                400,
                sprintf(
                    "The object %s is neither a branch nor a commit in repository %s.",
                    $representation->reference,
                    $repository->getName()
                )
            );
        }

        try {
            $this->branch_creation_executor->createNewBranch(
                $this->git_exec,
                self::BRANCH_PREFIX . $representation->branch_name,
                $representation->reference
            );
        } catch (CannotCreateNewBranchException $exception) {
            throw new RestException(
                500,
                "An error occurred while creating the branch: " . $exception->getMessage()
            );
        }
    }
}
