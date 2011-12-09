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


require_once 'GitViewsRepositoriesTraversalStrategyTest.class.php';
require_once dirname(__FILE__) .'/../include/GitViewsRepositoriesTraversalStrategy_Tree.class.php';
Mock::generate('GitViews');
Mock::generate('User');
Mock::generate('GitRepository');

/* 

Git
|--automaticTests
|  |-- Python
|  `-- Ruby
|-- deps
|   `-- 3rdparty
|       |-- cvsgraph
|       |-- geshi
|       `-- gitolite
`-- tools
    `-- lxc
*/       

class GitViewsRepositoriesTraversalStrategy_TreeTest extends GitViewsRepositoriesTraversalStrategyTest {
    
    public function __construct() {
        parent::__construct('GitViewsRepositoriesTraversalStrategy_Tree');
    }
    
    public function testInsertInTreeWithOneFolderShouldInsertInTheFirstLevel() {
        $view      = new MockGitViews();
        $traversal = new GitViewsRepositoriesTraversalStrategy_Tree($view);
        
        $tree = array();
        $path = array('a');
        $repo = new MockGitRepository();
        $traversal->insertInTree($tree, $repo, $path);
        
        $this->assertEqual($tree['a'], $repo);
    }
    
    public function testInsertInTreeWithEmptyPathShouldDoNothing() {
        $view      = new MockGitViews();
        $traversal = new GitViewsRepositoriesTraversalStrategy_Tree($view);
        
        $tree = array();
        $path = array();
        $repo = new MockGitRepository();
        $traversal->insertInTree($tree, $repo, $path);
        
        $this->assertEqual($tree, array());
    }
    
    public function testInsertInTreeShouldInsertAtTheLeaf() {
        $view      = new MockGitViews();
        $traversal = new GitViewsRepositoriesTraversalStrategy_Tree($view);
        
        $tree = array();
        $path = array('a', 'b', 'c');
        $repo = new MockGitRepository();
        $traversal->insertInTree($tree, $repo, $path);
        
        $this->assertEqual($tree['a']['b']['c'], $repo);
    }
    
    public function testInsertInTreeShouldInsertInSeveralBranches() {
        $view      = new MockGitViews();
        $traversal = new GitViewsRepositoriesTraversalStrategy_Tree($view);
        $tree      = array();
        
        $path  = array('a', 'b', 'c');
        $repo1 = new MockGitRepository();
        $traversal->insertInTree($tree, $repo1, $path);
        
        $path  = array('a', 'd');
        $repo2 = new MockGitRepository();
        $traversal->insertInTree($tree, $repo2, $path);
        
        $path  = array('b', 'z');
        $repo3 = new MockGitRepository();
        $traversal->insertInTree($tree, $repo3, $path);
        
        $this->assertEqual($tree['a']['b']['c'], $repo1);
        $this->assertEqual($tree['a']['d'], $repo2);
        $this->assertEqual($tree['b']['z'], $repo3);
    }
    
    public function testBuildTree() {
        
        
        $view      = new MockGitViews();
        $traversal = TestHelper::getPartialMock('GitViewsRepositoriesTraversalStrategy_Tree', array('getRepository'));
        $traversal->__construct($view);
        
        // Magic call that do stuff we want, yeah!
        $repositories = $this->getFlatTree($traversal);
        
        $tree = $traversal->getTree($repositories);
        $this->assertTrue(is_array($tree['automaticTests']));
        $this->assertIsA(($tree['automaticTests']['Python']), 'GitRepository');
    }
    
    public function getExpectedPattern($repositories) {
        $li_regexp_for_repository_representation = '';
        $nb_repositories                         = count($repositories);
        
        return '.*';
    }
}
?>
