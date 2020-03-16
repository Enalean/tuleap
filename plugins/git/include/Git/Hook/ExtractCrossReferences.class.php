<?php
/**
 * Copyright Enalean (c) 2013 - 2016. All rights reserved.
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
 * Extract references usage in commit messages
 */
class Git_Hook_ExtractCrossReferences
{

    /**
     * @var Git_Exec
     */
    private $git_exec;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    public function __construct(Git_Exec $git_exec, ReferenceManager $reference_manager)
    {
        $this->git_exec = $git_exec;
        $this->reference_manager = $reference_manager;
    }

    public function execute(Git_Hook_PushDetails $push_details, $commit_sha1)
    {
        $rev_id = $push_details->getRepository()->getFullName() . '/' . $commit_sha1;
        $text   = $this->git_exec->catFile($commit_sha1);
        $this->reference_manager->extractCrossRef(
            $text,
            $rev_id,
            Git::REFERENCE_NATURE,
            $push_details->getRepository()->getProject()->getId(),
            $push_details->getUser()->getId()
        );
    }
}
