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
 * Analyze a push a provide a high level object (PushDetails) that knows if push
 * is a branch creation or a tag deletion, etc.
 */
class Git_Hook_LogAnalyzer {
    const FAKE_EMPTY_COMMIT = '0000000000000000000000000000000000000000';

    /** @var Git_Exec */
    private $exec_repo;

    public function __construct(Git_Exec $git_exec) {
        $this->exec_repo = $git_exec;
    }

    /**
     *
     * Behaviour extracted from official email hook prep_for_email() function
     *
     * @param GitRepository $repository
     * @param PFUser $user
     * @param type $oldrev
     * @param type $newrev
     * @param type $refname
     * @return \Git_Hook_PushDetails
     */
    public function getPushDetails(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname) {
        if ($oldrev == self::FAKE_EMPTY_COMMIT) {
            $change_type   = Git_Hook_PushDetails::ACTION_CREATE;
            $revision_list = $this->exec_repo->revListSinceStart($refname, $newrev);
        } elseif ($newrev == self::FAKE_EMPTY_COMMIT) {
            $change_type   = Git_Hook_PushDetails::ACTION_DELETE;
            $revision_list = array();
        } else {
            $change_type   = Git_Hook_PushDetails::ACTION_UPDATE;
            $revision_list = $this->exec_repo->revList($oldrev, $newrev);
        }

        try {
            if ($change_type == Git_Hook_PushDetails::ACTION_DELETE) {
                $rev_type = $this->exec_repo->getObjectType($oldrev);
            } else {
                $rev_type = $this->exec_repo->getObjectType($newrev);
            }
        } catch(Git_Command_UnknownObjectTypeException $exception) {
            $rev_type = '';
        }

        return new Git_Hook_PushDetails(
            $repository,
            $user,
            $refname,
            $change_type,
            $rev_type,
            $revision_list
        );
    }
}

?>
