<?php
/**
 * Copyright (c) Enalean, 2012 - 2019. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;

require_once 'bootstrap.php';

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('GitRepositoryFactory');

class GitTest extends TuleapTestCase
{

    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView()
    {
        $usermanager = new MockUserManager();
        $request     = aRequest()->with('repo_id', 1)->build();

        $git = TestHelper::getPartialMock('Git', array('definePermittedActions', '_informAboutPendingEvents', 'addAction', 'addView', 'checkSynchronizerToken'));
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setAction('del');
        $git->setPermittedActions(array('del'));

        $repository = mock('GitRepository');
        $factory    = stub('GitRepositoryFactory')->getRepositoryById()->returns($repository);
        $git->setFactory($factory);

        $git->expectOnce('addAction', array('deleteRepository', '*'));
        $git->expectOnce('addView', array('index'));

        $git->request();
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonal()
    {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->expectOnce('_doDispatchForkRepositories');

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }

    public function testDispatchToForkRepositoriesIfRequestsPersonalAndNonMember()
    {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkRepositories', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'personal'));
        $git->setRequest($request);
        $git->expectNever('_doDispatchForkRepositories');

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', false);
        $git->user = $user;

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }

    public function testDispatchToForkCrossProjectIfRequestsProject()
    {
        $git = TestHelper::getPartialMock('Git', array('_doDispatchForkCrossProject', 'addView'));
        $request = new Codendi_Request(array('choose_destination' => 'project'));
        $git->setRequest($request);

        $factory = new MockGitRepositoryFactory();
        $git->setFactory($factory);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true);
        $git->user = $user;

        $git->expectOnce('_doDispatchForkCrossProject');
        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, null);
    }
}
