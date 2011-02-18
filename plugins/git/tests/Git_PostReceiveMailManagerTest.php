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

require_once dirname(__FILE__).'/../include/GitDao.class.php';
require_once dirname(__FILE__).'/../include/GitRepository.class.php';

require_once('common/user/User.class.php');
Mock::generate('GitRepository');
Mock::generate('User');
Mock::generate('Project');
Mock::generate('GitDao');

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

}
?>