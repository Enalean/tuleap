<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
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
 * Base class for a svn hook object
 */
abstract class SVN_Hook
{

    /** @var SVN_Hooks */
    private $svn_hooks;

    /** @var SVN_CommitMessageValidator */
    protected $message_validator;

    public function __construct(
        SVN_Hooks $svn_hooks,
        SVN_CommitMessageValidator $message_validator
    ) {
        $this->svn_hooks         = $svn_hooks;
        $this->message_validator = $message_validator;
    }

    /**
     * @param string $repository
     *
     * @return Project
     */
    protected function getProjectFromRepositoryPath($repository)
    {
        return $this->svn_hooks->getProjectFromRepositoryPath($repository);
    }
}
