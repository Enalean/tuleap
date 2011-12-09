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

require_once 'GitViewsRepositoriesTraversalStrategy.class.php';
require_once 'GitRepository.class.php';
require_once 'GitViews.class.php';

/**
 * Traverse a list of repositories and provides a tree in a table view
 */
class GitViewsRepositoriesTraversalStrategy_Tree extends GitViewsRepositoriesTraversalStrategy {
    
    /**
     * Constructor
     *
     * @param GitViews $view The GitViews
     */
    public function __construct(GitViews $view) {
        parent::__construct();
        $this->view = $view;
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
    protected function getLabel(GitRepository $repository, $isInitialized, $accessType, $repoName) {
        return '';
    }
    
       
    /**
     * Get the main wrapper of the whole representation
     *
     * @param string $inner The inner string
     *
     * @return string the $inner encapsuled in the wrapper
     */
    protected function getMainWrapper($inner) {
        return 'c';
    }
    
    /**
     * Get Item wrapper
     *
     * @param GitRepository $repo  the string representation of the item
     * @param string        $inner the string representation of the item
     *
     * @return string the $inner encapsulated in its own wrapper
     */
    protected function getItemWrapper(GitRepository $repo, $inner) {
        return '';
    }
    
    /**
     * Get group wrapper
     *
     * @param string $label the name of the group
     * @param string $inner the string representation of a group of items
     *
     * @return string the $inner encapsulated in its own wrapper
     */
    protected function getGroupWrapper($label, $inner) {
        return '';
    }
    
    public function getTree(array $repositories) {
        $tree = array();
        foreach ($repositories as $repoId => $row) {
            $path = explode('/', $row['repository_name']);
            $repo = $this->getRepository($row);
            $this->insertInTree($tree, $repo, $path);
        }
        return $tree;
    }
    
    public function insertInTree(&$tree, GitRepository $repository, array $path) {
        if (count($path)) {
            $head = array_shift($path);
            if (count($path)) {
                $this->insertInTree($tree[$head], $repository, $path);
            } else {
                $tree[$head] = $repository;
            }
        }
    }
}
?>
