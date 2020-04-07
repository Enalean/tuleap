<?php
/**
 * Copyright Enalean (c) 2014 - 2016. All rights reserved.
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
 * I'm responsible of checking that commit message is valid
 */
class SVN_CommitMessageValidator
{

    /** @var ReferenceManager */
    private $reference_manager;

    public function __construct(ReferenceManager $reference_manager)
    {
        $this->reference_manager = $reference_manager;
    }

    public function assertCommitMessageIsValid(Project $project, $commit_message)
    {
        if (
            $project->isSVNMandatoryRef()
            && ! $this->reference_manager->stringContainsReferences($commit_message, $project)
        ) {
            throw new Exception('Commit message must contains a reference');
        }
    }
}
