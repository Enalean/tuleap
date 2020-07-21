<?php
/**
 * Copyright (c) Enalean 2016 - 2018. All rights reserved
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

namespace Tuleap\SVN\Repository;

use Rule;
use Project;
use Tuleap\SVN\Dao;

/**
 * Check if a project name is valid
 *
 * This extends the user name validation
 */
class RuleName extends Rule
{
    public const PATTERN_REPOSITORY_NAME = '[a-zA-Z][A-Za-z0-9-_.]{2,254}';

    private $project;
    private $dao;

    public function __construct(Project $project, Dao $dao)
    {
        $this->project = $project;
        $this->dao     = $dao;
    }

    public function isValid($val)
    {
        return preg_match('/^' . self::PATTERN_REPOSITORY_NAME . '\z/i', $val) &&
            ! $this->doesNameAlreadyExisting($val);
    }

    private function doesNameAlreadyExisting($name_repository)
    {
        if ($this->dao->doesRepositoryAlreadyExist($name_repository, $this->project)) {
            $this->error = $this->getErrorRepositoryExists();
            return true;
        }
        return false;
    }

    private function getErrorRepositoryExists()
    {
        return dgettext('tuleap-svn', 'Repository name is already used in this project.');
    }
}
