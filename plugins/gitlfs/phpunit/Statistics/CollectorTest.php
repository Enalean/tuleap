<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Statistics;

use Mockery;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationDAO;
use Tuleap\GitLFS\LFSObject\LFSObjectDAO;

class CollectorTest extends \PHPUnit_Framework_TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Mockery\MockInterface
     */
    private $disk_usage_dao;

    /**
     * @var Mockery\MockInterface
     */
    private $action_authorization_dao;

    /**
     * @var Mockery\MockInterface
     */
    private $lfs_object_dao;

    /**
     * @var Mockery\MockInterface
     */
    private $git_repository_factory;

    public function setUp()
    {
        $this->disk_usage_dao           = Mockery::mock(\Statistics_DiskUsageDao::class);
        $this->action_authorization_dao = Mockery::mock(ActionAuthorizationDAO::class);
        $this->lfs_object_dao           = Mockery::mock(LFSObjectDAO::class);
        $this->git_repository_factory   = Mockery::mock(\GitRepositoryFactory::class);

        $this->collector = new Collector(
            $this->disk_usage_dao,
            $this->action_authorization_dao,
            $this->lfs_object_dao,
            $this->git_repository_factory
        );
    }

    public function testItShouldReturn0IfProjectHasNoRepositories()
    {
        $this->git_repository_factory->shouldReceive("getAllRepositoriesOfProject")->andReturn(array());

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive("getId")->andReturn(100);

        $params  = [
            "project_row" => ["group_id" => 100],
            "project" => $project
        ];

        $this->disk_usage_dao->shouldReceive("addGroup")->withArgs([100, \gitlfsPlugin::SERVICE_SHORTNAME, 0, Mockery::any()]);

        $this->collector->proceedToDiskUsageCollection($params);
    }

    public function testItShouldAddBothAuthorizationsAndObjectsSizes()
    {
        $git_repository = Mockery::mock(\GitRepository::class);
        $git_repository->shouldReceive("getId")->andReturn(1);
        $this->git_repository_factory->shouldReceive("getAllRepositoriesOfProject")->andReturn(array($git_repository));

        $authorization = ["object_size" => 30];
        $this->action_authorization_dao->shouldReceive("searchAuthorizationTypeByRepositoriesIdsAndExpiration")->andReturn(array($authorization));

        $object = ["object_size" => 70];
        $this->lfs_object_dao->shouldReceive("searchObjectsByRepositoryIds")->andReturn(array($object));

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive("getId")->andReturn(100);

        $params  = [
            "project_row" => ["group_id" => 100],
            "project"     => $project
        ];

        $this->disk_usage_dao->shouldReceive("addGroup")->withArgs([100, \gitlfsPlugin::SERVICE_SHORTNAME, 100, Mockery::any()]);

        $this->collector->proceedToDiskUsageCollection($params);
    }
}
