<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tuleap\Project\HeartbeatsEntryCollection;

final class LatestHeartbeatsCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private \GitRepositoryFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface $factory;
    private LatestHeartbeatsCollector $collector;
    private \Project $project;
    private PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = new \Project(['group_id' => 101]);
        $this->user    = new PFUser(['user_id' => 200, 'language_id' => 'en']);

        $dao = \Mockery::mock(\Git_LogDao::class);
        $dao->shouldReceive('searchLatestPushesInProject')
            ->with(101, HeartbeatsEntryCollection::NB_MAX_ENTRIES)
            ->andReturn([
                ['repository_id' => 1, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
                ['repository_id' => 2, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
                ['repository_id' => 3, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
            ]);

        $this->factory = \Mockery::spy(\GitRepositoryFactory::class);
        $this->declareRepository(1, true);
        $this->declareRepository(2, false);
        $this->declareRepository(3, true);

        $this->collector = new LatestHeartbeatsCollector(
            $this->factory,
            $dao,
            \Mockery::spy(\Git_GitRepositoryUrlManager::class),
            \Mockery::spy(\UserManager::class),
        );
    }

    private function declareRepository($id, $user_can_read): void
    {
        $repository = \Mockery::spy(\GitRepository::class)->shouldReceive('getId')->andReturns($id)->getMock();

        $repository->shouldReceive('userCanRead')->andReturns($user_can_read);
        $repository->shouldReceive('getProject')->andReturns($this->project);

        $this->factory->shouldReceive('getRepositoryById')->with($id)->andReturns($repository);
    }

    public function testItCollectsOnlyPushesForRepositoriesUserCanView(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertCount(2, $collection->getLatestEntries());
    }

    public function testItInformsThatThereIsAtLeastOneActivityThatUserCannotRead(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertTrue($collection->areThereActivitiesUserCannotSee());
    }
}
