<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * Base class to traverse a list of repositories
 */
abstract class GitViewsRepositoriesTraversalStrategy
{

    public function __construct()
    {
    }

    /**
     * Display the list of repositories
     *
     * @param array $repositories Array of raw representation of repositories, indexed by repository id
     * @param PFUser  $user         The user who traverse the forest
     *
     * @return string
     */
    public function fetch(array $repositories, PFUser $user)
    {
        if (empty($repositories)) {
            return '';
        }
        return $this->getMainWrapper($this->_makeRepositoryList($repositories, $user));
    }

    /**
     * @return string
     */
    protected function _makeRepositoryList(array $repositories, PFUser $user)
    {
        $html = '';
        foreach ($repositories as $repository) {
            $repoName = $repository[GitDao::REPOSITORY_NAME];
            $delDate  = $repository[GitDao::REPOSITORY_DELETION_DATE];
            $r = $this->getRepository($repository);

            if (! $r->userCanRead($user)) {
                continue;
            }
            //we do not want to display deleted repository
            if ($delDate != '0000-00-00 00:00:00') {
                continue;
            }

            $html .= $this->getItemWrapper($r, $repoName);
        }
        return $html;
    }

    /**
     * Wrapper for GitRepository for unit testing purpose
     *
     * @param array $row data of the repository to instantiate
     *
     * @return GitRepository
     */
    protected function getRepository($row)
    {
        $r = new GitRepository();
        $r->setId($row[GitDao::REPOSITORY_ID]);
        $r->load();
        return $r;
    }

    /**
     * Get the main wrapper of the whole representation
     *
     * @param string $inner The inner string
     *
     * @return string the $inner encapsuled in the wrapper
     */
    abstract protected function getMainWrapper($inner);

    /**
     * Get Item wrapper
     *
     * @param GitRepository $repo  the string representation of the item
     * @param string        $inner the string representation of the item
     *
     * @return string the $inner encapsulated in its own wrapper
     */
    abstract protected function getItemWrapper(GitRepository $repo, $inner);
}
