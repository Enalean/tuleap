<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitActionsProjectPrivacyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::spy(\GitDao::class);
        $this->factory = \Mockery::spy(\GitRepositoryFactory::class);
    }

    public function testItDoesNothingWhenThereAreNoRepositories(): void
    {
        $project_id = 99;
        $this->dao->shouldReceive('getProjectRepositoryList')->with($project_id)->andReturns(array());
        $this->changeProjectRepositoriesAccess($project_id, true);
        $this->changeProjectRepositoriesAccess($project_id, false);
    }

    public function testItDoesNothingWeAreMakingItTheProjectPublic(): void
    {
        $project_id = 99;
        $is_private = false;
        $repo_id = 333;
        $repo = Mockery::mock(\GitRepository::class)->shouldReceive('setAccess')->never()->getMock();
        $this->dao->shouldReceive('getProjectRepositoryList')->with($project_id)->andReturns(array($repo_id => null));
        $this->factory->shouldReceive('getRepositoryById')->with($repo_id)->andReturns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function testItMakesRepositoriesPrivateWhenProjectBecomesPrivate(): void
    {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = Mockery::mock(\GitRepository::class)
            ->shouldReceive('setAccess')
            ->with(GitRepository::PRIVATE_ACCESS)
            ->once()
            ->andReturns("whatever")
            ->getMock();

        $repo->shouldReceive('getAccess');
        $repo->shouldReceive('changeAccess')->once();

        $this->dao->shouldReceive('getProjectRepositoryList')->with($project_id)->andReturns(array($repo_id => null));
        $this->factory->shouldReceive('getRepositoryById')->with($repo_id)->andReturns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function testItDoesNothingIfThePermissionsAreAlreadyCorrect(): void
    {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = Mockery::mock(\GitRepository::class)->shouldReceive('setAccess')->never()->getMock();
        $repo->shouldReceive('getAccess')->andReturns(GitRepository::PRIVATE_ACCESS);
        $repo->shouldReceive('changeAccess')->andReturns("whatever");
        $this->dao->shouldReceive('getProjectRepositoryList')->with($project_id)->andReturns(array($repo_id => null));
        $this->factory->shouldReceive('getRepositoryById')->with($repo_id)->andReturns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function testItHandlesAllRepositoriesOfTheProject(): void
    {
        $project_id = 99;
        $is_private = true;
        $repo_id1 = 333;
        $repo_id2 = 444;

        $repo1 = Mockery::mock(\GitRepository::class)
            ->shouldReceive('setAccess')
            ->with(GitRepository::PRIVATE_ACCESS)
            ->once()
            ->andReturns("whatever")
            ->getMock();

        $repo2 = Mockery::mock(\GitRepository::class)
            ->shouldReceive('setAccess')
            ->with(GitRepository::PRIVATE_ACCESS)
            ->once()
            ->andReturns("whatever")
            ->getMock();

        $repo1->shouldReceive('getAccess');
        $repo2->shouldReceive('getAccess');

        $repo1->shouldReceive('changeAccess')->once();
        $repo2->shouldReceive('changeAccess')->once();

        $this->dao->shouldReceive('getProjectRepositoryList')->with($project_id)->andReturns(array($repo_id1 => null, $repo_id2 => null));
        $this->factory->shouldReceive('getRepositoryById')->with($repo_id1)->andReturns($repo1);
        $this->factory->shouldReceive('getRepositoryById')->with($repo_id2)->andReturns($repo2);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    private function changeProjectRepositoriesAccess($project_id, $is_private)
    {
        return GitActions::changeProjectRepositoriesAccess($project_id, $is_private, $this->dao, $this->factory);
    }
}
