<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


abstract class GitRepositoryCreatorImpl implements GitRepositoryCreator
{

    public function isNameValid($name)
    {
        $len = strlen($name);
        return 1 <= $len && $len < GitDao::REPO_NAME_MAX_LENGTH &&
               ! preg_match('`[^' . $this->getAllowedCharsInNamePattern() . ']`', $name) &&
               ! preg_match('`(?:^|/)\.`', $name) && //do not allow dot at the beginning of a world
               ! preg_match('%/$|^/%', $name) && //do not allow a slash at the beginning nor the end
               ! preg_match('`\.\.`', $name) && //do not allow double dots (prevent path collisions)
               ! preg_match('/\.git$|\.git\//', $name) && //do not allow ".git" at the end of a repository, Tuleap will automatically add it on te git repository and the previous repository are not git repositories"
               ! preg_match('%^u/%', $name);
    }

    /**
     * Get the regexp pattern to use for name repository validation
     *
     * @return string
     */
    public function getAllowedCharsInNamePattern()
    {
        //alphanums, underscores, slashes and dash
        return 'a-zA-Z0-9/_.-';
    }
}
