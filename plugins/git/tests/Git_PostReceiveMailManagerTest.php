<?php
/*
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generatePartial('Git_PostReceiveMailManager', 'PostReceiveMailManagerTestVersion', array('addMail', '_getDao','removeMailByRepository','_getGitDao', '_getGitRepository'));
Mock::generatePartial('Git_PostReceiveMailManager', 'PostReceiveMailManagerTestRemoveRepository', array('addMail', '_getDao','_getGitDao', '_getGitRepository'));
Mock::generate('Git_PostReceiveMailDao');

require_once('common/user/User.class.php');
Mock::generate('GitRepository');
Mock::generate('PFUser');
Mock::generate('Project');
Mock::generate('GitDao');
Mock::generate('GitBackend');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/include/Response.class.php');
Mock::generate('Response');

class Git_PostReceiveMailManagerTest extends TuleapTestCase {

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Response'] = new MockResponse();
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);
        unset($GLOBALS['Response']);
        parent::tearDown();
    }

    public function testRemoveMailByProjectPrivateRepositoryUserStillMember(){
        $prm = new PostReceiveMailManagerTestVersion();

        $user = mock('PFUser');
        $user->setReturnValue('isMember', True);
        $user->setReturnValue('getEmail', "codendiadm@codendi.org");

        $prj = new MockProject($this);
        $prj->setReturnValue('getId', 1750);

        $gitDao = new MockGitDao($this);
        $prm->setReturnValue('_getGitDao',$gitDao);

        $gitDao->expectNever('getProjectRepositoryList');

        $prm->removeMailByProjectPrivateRepository($prj->getId(), $user);

    }

    public function testRemoveMailByProjectPrivateRepository(){
        $prm = new PostReceiveMailManagerTestVersion();

        $user = mock('PFUser');
        $user->setReturnValue('isMember', False);
        $user->setReturnValue('getEmail', "codendiadm@codendi.org");

        $prj = new MockProject($this);
        $prj->setReturnValue('getId', 1750);

        $repositoryList = array(
        array('project_id' => '1750' , 'repository_id' => 1035)
        );

        $gitDao = new MockGitDao($this);
        $prm->setReturnValue('_getGitDao',$gitDao);
        $gitDao->setReturnValue('getProjectRepositoryList', $repositoryList);

        $repo = new MockGitRepository($this);
        $prm->setReturnValue('_getGitRepository',$repo);
        $repo->setReturnValue('load',True);
        $repo->setReturnValue('isPrivate',True);

        $prm->expectOnce('removeMailByRepository', array($repo , "codendiadm@codendi.org"));
        $prm->removeMailByProjectPrivateRepository($prj->getId(), $user);
    }

    public function testRemoveMailByProjectPrivateRepositoryErrorDaoRemoving(){
        $prm = new PostReceiveMailManagerTestVersion();

        $user = mock('PFUser');
        $user->setReturnValue('isMember', False);
        $user->setReturnValue('getEmail', "codendiadm@codendi.org");

        $prj = new MockProject($this);
        $prj->setReturnValue('getId', 1750);

        $repositoryList = array(
        array('project_id' => '1750', 'repository_id' => 2515),
        );

        $gitDao = new MockGitDao($this);
        $prm->setReturnValue('_getGitDao',$gitDao);
        $gitDao->setReturnValue('getProjectRepositoryList', $repositoryList);

        foreach ($repositoryList as $row) {

            $repo = new MockGitRepository($this);
            $prm->setReturnValue('_getGitRepository',$repo);
            $repo->setReturnValue('isPrivate',True);
            $repo->setReturnValue('load',True);

            $backend = new MockGitBackend();
            $repo->SetReturnValue('getBackend', $backend);
        }

        $prm->setReturnValue('removeMailByRepository', False);
        $GLOBALS['Language']->setReturnValue('getText','Mail not removed');
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', $GLOBALS['Language']->getText('plugin_git','dao_error_remove_notification')));
        $prm->removeMailByProjectPrivateRepository($prj->getId(), $user);
    }

    public function testRemoveMailByRepository(){
        $prm = new PostReceiveMailManagerTestRemoveRepository();
        $dao = new MockGit_PostReceiveMailDao();
        $prm->dao = $dao;

        $repo = new MockGitRepository($this);

        $backend = new MockGitBackend();
        $repo->SetReturnValue('getBackend', $backend);

        $prm->dao->setReturnValue('removeNotification', True);

        $repo->expectOnce('loadNotifiedMails');
        $backend->expectOnce('changeRepositoryMailingList');

        $prm->removeMailByRepository($repo, "codendiadm@codendi.org");
    }
}
