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
 * This class is a wrapper for call to api/reference/extractCross
 *
 * This allow applications that run on the server to extract cross references
 * without having access to the database
 */
class Git_Hook_ExtractCrossReferences {

    private $git_exec;
    private $reference_manager;

    public function __construct(Git_Exec $git_exec, ReferenceManager $reference_manager) {
        $this->git_exec = $git_exec;
        $this->reference_manager = $reference_manager;
    }

    public function execute(GitRepository $repository, PFUser $user, $commit, $refname) {
        $rev_id = $repository->getFullName().'/'.$commit;
        $text   = $this->git_exec->catFile($commit);
        $GLOBALS['group_id'] = $repository->getProject()->getId();
        $this->reference_manager->extractCrossRef($text, $rev_id, Git::REFERENCE_NATURE, $repository->getProject()->getId(), $user->getId());
    }
}

?>
