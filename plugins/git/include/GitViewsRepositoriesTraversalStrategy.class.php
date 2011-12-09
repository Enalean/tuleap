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
abstract class GitViewsRepositoriesTraversalStrategy {
    
    public function __construct() {
    }
    
    /**
     * Display the list of repositories
     *
     * @param array $repositories Array of raw representation of repositories, indexed by repository id
     * @param User  $user         The user who traverse the forest
     *
     * @return string
     */
    public function fetch(array $repositories, User $user) {
        if (empty($repositories)) {
            return '';
        }
        $parentChildrenAssoc = array();
        foreach ( $repositories as $repoId => $repoData ) {
            if ( !empty($repoData[GitDao::REPOSITORY_PARENT]) ) {
                $parentId = $repoData[GitDao::REPOSITORY_PARENT];
                $parentChildrenAssoc[$parentId][] = $repoData[GitDao::REPOSITORY_ID];
            }
            else {
                $parentChildrenAssoc[0][] = $repoId;
            }
        }
        return $this->getMainWrapper($this->_makeRepositoryTree($parentChildrenAssoc, 0, $repositories, $user));
    }
    
    /**
     * @return string
     */
    protected function _makeRepositoryTree(&$flatTree, $currentId, array $data, User $user) {
        $html = '';
        foreach ( $flatTree[$currentId] as $childId ) {
            $repoName = $data[$childId][GitDao::REPOSITORY_NAME];
            $repoDesc = $data[$childId][GitDao::REPOSITORY_DESCRIPTION];
            $delDate  = $data[$childId][GitDao::REPOSITORY_DELETION_DATE];
            $isInit   = $data[$childId][GitDao::REPOSITORY_IS_INITIALIZED];
            $access   = $data[$childId][GitDao::REPOSITORY_ACCESS];
            //needs to be checked on filesystem (GitDao::getRepositoryList do not check)
            //TODO move this code to GitBackend and write a new getRepositoryList function ?
            //TODO find a better way to do that to avoid the ton of SQL requests!
            $r = $this->getRepository($data[$childId]);
            if ( $isInit == 0 ) {
                $isInit = $r->isInitialized();
            }

            if (!$r->userCanRead($user)) {
                continue;
            }
            //we do not want to display deleted repository
            if ( $delDate != '0000-00-00 00:00:00' ) {
                continue;
            }
            
            $accessType = $this->view->fetchAccessType($access, $data[$childId][GitDao::REPOSITORY_BACKEND_TYPE] == GitDao::BACKEND_GITOLITE);
            
            //TODO Why the hell do we need to use isInit or repoName? Isn't it a property of the repo?
            $item_representation = $this->getLabel($r, $isInit, $accessType, $repoName);

            if ( !empty($flatTree[$childId]) ) {
                $item_representation .= $this->getGroupWrapper($repoName, $this->_makeRepositoryTree($flatTree, $childId, $data, $user));
            }
            $html .= $this->getItemWrapper($r, $item_representation);
        }
        return $html;
    }

    /**
     * Get the repository label
     *
     * @param GitRepository $repository    Teh repository
     * @param bool          $isInitialized true of the repo is initialized
     * @param string        $accessType    The access type of the repository
     * @param string        $repoName      The name of the repository
     *
     * @return string
     */
    protected abstract function getLabel(GitRepository $repository, $isInitialized, $accessType, $repoName);
    
    /**
     * Wrapper for GitRepository for unit testing purpose
     *
     * @param array $row data of the repository to instantiate
     *
     * @return GitRepository
     */
    protected function getRepository($row) {
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
    protected abstract function getMainWrapper($inner);
    
    /**
     * Get Item wrapper
     *
     * @param GitRepository $repo  the string representation of the item
     * @param string        $inner the string representation of the item
     *
     * @return string the $inner encapsulated in its own wrapper
     */
    protected abstract function getItemWrapper(GitRepository $repo, $inner);
    
    /**
     * Get group wrapper
     *
     * @param string $label the name of the group
     * @param string $inner the string representation of a group of items
     *
     * @return string the $inner encapsulated in its own wrapper
     */
    protected abstract function getGroupWrapper($label, $inner);
}
?>
