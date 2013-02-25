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
Mock::generate('PFUser');
Mock::generate('Git_Backend_Gitolite');

abstract class GitViewsRepositoriesTraversalStrategyTest extends TuleapTestCase {
    
    public function __construct($classname) {
        parent::__construct();
        $this->classname = $classname;
    }
    
    public function testEmptyListShouldReturnEmptyString() {
        $view = new MockGitViews();
        $user = mock('PFUser');
        $repositories = array();
        $strategy = new $this->classname($view);
        $this->assertIdentical('', $strategy->fetch($repositories, $user));
    }
    
    public function testFlatTreeShouldReturnRepresentation() {
        $view = new MockGitViews();
        $user = mock('PFUser');
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
        foreach ($repositories as $row) {
            /* @var $repository GitRepository */
            $repository = partial_mock('GitRepository', array('userCanRead'));
            $repository->setId($row['repository_id']);
            $repository->setName($row['repository_name']);
            $repository->setDescription($row['repository_description']);
            stub($repository)->userCanRead()->returns(true);
            if ($row['repository_backend_type'] == 'gitolite') {
                $repository->setBackend(mock('Git_Backend_Gitolite'));
            } else {
                $repository->setBackend(mock('GitBackend'));
            }
            $strategy->setReturnValue('getRepository', $repository, array($row));
        }
        return $repositories;
    }
}
?>
