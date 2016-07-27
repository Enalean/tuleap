<?php
/**
* Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Commit\CommitInfo;
use Mock;
use TuleapTestCase;

require_once __DIR__ .'/../bootstrap.php';

class LauncherTest extends TuleapTestCase {

    private $logger;
    private $project;
    private $repository;
    private $commit_info;

    public function setUp() {
        parent::setUp();

        $this->logger      = mock('Tuleap\HudsonSvn\SvnBackendLogger');
        $this->project     = stub("Project")->getId()->returns(101);
        $this->repository  = new Repository(1, "repository_name", '', '', $this->project);
        $this->commit_info = new CommitInfo();
        $this->commit_info->setChangedDirectories(array("/", "a", "a/trunk", "a/trunk/b", "a/trunk/c"));

        stub($this->project)->usesService('hudson')->returns(true);
    }

    public function tearDown() {
        unset($this->logger);
        unset($this->project);
        unset($this->repository);
    }

    private function launchAndTest($jobs, $repository, $commit_info, $call_count) {
        $factory   = mock('Tuleap\HudsonSvn\Job\Factory');
        $ci_client = mock('Jenkins_Client');

        stub($factory)->getJobsByRepository()->returns($jobs);

        $launcher = new Launcher($factory, $this->logger, $ci_client);

        $launcher->launch($repository, $commit_info);
        $ci_client->expectCallCount('launchJobBuild', $call_count);
    }


    public function itTestJenkinsJobAreTriggeredOnCommit(){
        $jobs = array(new Job ('1', '1', '/', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('2', '1', '/a', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('3', '1', '/a/trunk', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('4', '1', '/*/trunk', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('5', '1', '/*/*', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('6', '1', '/a/*', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('7', '1', '/a/*/b', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('8', '1', '/a/*/c', 'https://ci.exemple.com/job/Job_Example/', ''));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "1");

        $jobs = array(new Job ('9', '1', '/b', 'https://ci.exemple.com/job/Job_Example/', '' ));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "0");

        $jobs = array(new Job ('10', '1', '/a/trunked*', 'https://ci.exemple.com/job/Job_Example/', '' ));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "0");

        $jobs = array(new Job ('10', '1', '/a/root/c', 'https://ci.exemple.com/job/Job_Example/', '' ));
        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "0");
    }

    public function itTestJenkinsJobAreNotLaunchedTwiceOnSameCommit(){
        $jobs = array(
                new Job ('1', '1', '/',        'https://ci.exemple.com/job/Job_Example/', '' ),
                new Job ('2', '1', '/a/',      'https://ci.exemple.com/job/Job_Example/', '' ),
                new Job ('3', '1', '/a/*',     'https://ci.exemple.com/job/Job_Example/', '' ),
                new Job ('4', '1', '/a/trunk', 'https://ci.exemple.com/job/Job_Example/', '' ),
                new Job ('5', '1', '/a/*/b',   'https://ci.exemple.com/job/Job_Example/', '' ),
                new Job ('6', '1', '/a/',      'https://ci.exemple.com/job/New_Job_Example/', '' ),
                new Job ('7', '1', '/a/*',     'https://ci.exemple.com/job/New_Job_Example/', '' ),
                new Job ('8', '1', '/a/trunk', 'https://ci.exemple.com/job/New_Job_Example/', '' ),
                new Job ('9', '1', '/',        'https://ci.exemple.com/job/Another_Job_Example/', '' )
            );

        $this->launchAndTest($jobs, $this->repository, $this->commit_info, "3");
    }

}