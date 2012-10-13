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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once dirname(__FILE__) .'/../include/Git_Backend_Gitolite.class.php';

Mock::generate('GitViews');
Mock::generate('User');
Mock::generate('Git_Backend_Gitolite');

abstract class GitViewsRepositoriesTraversalStrategyTest extends TuleapTestCase {
    
    public function __construct($classname) {
        parent::__construct();
        $this->classname = $classname;
    }
    
    public function testEmptyListShouldReturnEmptyString() {
        $view = new MockGitViews();
        $user = new MockUser();
        $repositories = array();
        $strategy = new $this->classname($view);
        $this->assertIdentical('', $strategy->fetch($repositories, $user));
    }
    
    public function testFlatTreeShouldReturnRepresentation() {
        $view = new MockGitViews();
        $user = new MockUser();
        $strategy = TestHelper::getPartialMock($this->classname, array('getRepository'));
        
        $repositories    = $this->getFlatTree($strategy);
        $expectedPattern = $this->getExpectedPattern($repositories);
        
        $strategy->__construct($view);
        $this->assertPattern('`'. $expectedPattern .'`', $strategy->fetch($repositories, $user));
    }
    
    public abstract function getExpectedPattern($repositories);
    
    protected function getFlatTree($strategy) {
        //go find the variable $repositories
        include dirname(__FILE__) .'/_fixtures/flat_tree_of_repositories.php'; 
        $gitolite_backend = new MockGit_Backend_Gitolite();
        foreach ($repositories as $row) {
            $r = mock('GitRepository');
            $r->setReturnValue('getId', $row['repository_id']);
            $r->setReturnValue('getDescription', $row['repository_description']);
            $r->setReturnValue('userCanRead', true);
            if ($row['repository_backend_type'] == 'gitolite') {
                $r->setReturnValue('getBackend', $gitolite_backend);
            }
            $strategy->setReturnValue('getRepository', $r, array($row));
        }
        return $repositories;
    }
}
?>
