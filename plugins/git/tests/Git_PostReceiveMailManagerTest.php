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
require_once dirname(__FILE__).'/../include/Git_PostReceiveMailManager.class.php';
Mock::generate('Git_PostReceiveMailManager');
Mock::generatePartial('Git_PostReceiveMailManager', 'PostReceiveMailManagerTestVersion', array('addMail', '_getDao','removeMailByRepository','_getGitDao', '_getGitRepository'));
Mock::generate('Git_PostReceiveMailDao');

require_once dirname(__FILE__).'/../include/GitDao.class.php';
require_once dirname(__FILE__).'/../include/GitRepository.class.php';

require_once('common/user/User.class.php');
Mock::generate('GitRepository');
Mock::generate('User');
Mock::generate('Project');
Mock::generate('GitDao');
Mock::generate('GitBackend');

class Git_PostReceiveMailManagerTest extends UnitTestCase {

     public function testRemoveMailByProjectPrivateRepositoryUserStillMember(){
     $prm = new PostReceiveMailManagerTestVersion();

     $user = new MockUser($this);
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
     $dao = new MockGit_PostReceiveMailDao();
     $prm->dao = $dao;

     $user = new MockUser($this);
     $user->setReturnValue('isMember', False);
     $user->setReturnValue('getEmail', "codendiadm@codendi.org");

     $prj = new MockProject($this);
     $prj->setReturnValue('getId', 1750);

     $repositoryList = array(
         array('project_id' => '1750', 'repository_id' => 2515),
         array('project_id' => '1750' , 'repository_id' => 915),
         array('project_id' => '1750' , 'repository_id' => 1035)
     );

     $gitDao = new MockGitDao($this);
     $prm->setReturnValue('_getGitDao',$gitDao);
     $gitDao->setReturnValue('getProjectRepositoryList', $repositoryList);

     foreach ($repositoryList as $row) {

     $repo = new MockGitRepository($this);
     $prm->setReturnValue('_getGitRepository',$repo);
     $repo->setReturnValue('isPrivate',True);
     }

     $prm->dao->expectAt(1, 'removeNotification', array(915 , "codendiadm@codendi.org"));
     $prm->dao->expectCallCount('removeNotification',3);
     $prm->removeMailByProjectPrivateRepository($prj->getId(), $user);
     }

    public function testRemoveMailByProjectPrivateRepositoryErrorDaoRemoving(){
        $prm = new PostReceiveMailManagerTestVersion();
        $dao = new MockGit_PostReceiveMailDao();
        $prm->dao = $dao;

        $user = new MockUser($this);
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

        $prm->dao->setReturnValue('removeNotification', False);
        $repo->expectNever('setNotifiedMails');
        $backend->expectNever('changeRepositoryMailingList');
        $prm->removeMailByProjectPrivateRepository($prj->getId(), $user);
    }

}
?>