<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once (dirname(__FILE__).'/../include/Git.class.php');
require_once(dirname(__FILE__).'/../../../src/common/valid/ValidFactory.class.php');
require_once(dirname(__FILE__).'/../../../src/common/user/UserManager.class.php');
Mock::generatePartial('Git', 'GitSpy', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView'));
Mock::generate('UserManager');
class GitTest extends TuleapTestCase {
    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView() {
        $git = new GitSpy();
        $git->expectOnce('addAction', array('deleteRepository', '*'));
        $git->expectOnce('addView', array('index'));
        
        $usermanager = new MockUserManager();
        $unimportantGroupId = 101;
        $request = new HTTPRequest();

        $git->_addInstanceVars($request, $usermanager, 'del', array('del'), $unimportantGroupId);
        $git->request();
    }
    
    public function testTheForkExternalRouteExecutesForkExternalRepositoryWithForkRepositoriesView() {
        $user = new User();
        $toProject = 100;
        $repos = array('my-repo');
        $usermanager = new MockUserManager();
        $usermanager->setReturnValue('getCurrentUser', $user);

        $git = new GitSpy();
        $git->expectOnce('addAction', array('forkExternals', array($toProject, $repos, $user)));
        $git->expectOnce('addView', array('forkRepositories'));

        $request = new Codendi_Request(array(
                                        'to_project' => $toProject,
                                        'repos' => $repos));

        $git->_addInstanceVars($request, $usermanager);
        $git->_dispatchActionAndView('fork_externals', null, null, $user);
    }
}


?>
