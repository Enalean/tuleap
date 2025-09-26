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

use Git_GitRepositoryUrlManager;
use Git_LogDao;
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use Project;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LatestHeartbeatsCollectorTest extends TestCase
{
    private LatestHeartbeatsCollector $collector;
    private Project $project;
    private PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = new Project(['group_id' => 101]);
        $this->user    = new PFUser(['user_id' => 200, 'language_id' => 'en']);

        $dao = $this->createMock(Git_LogDao::class);
        $dao->method('searchLatestPushesInProject')
            ->with(101, HeartbeatsEntryCollection::NB_MAX_ENTRIES)
            ->willReturn([
                ['repository_id' => 1, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
                ['repository_id' => 2, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
                ['repository_id' => 3, 'user_id' => 101, 'push_date' => 1234, 'commits_number' => 1],
            ]);

        $factory      = $this->createMock(GitRepositoryFactory::class);
        $repository_1 = $this->declareRepository(1, true);
        $repository_2 = $this->declareRepository(2, false);
        $repository_3 = $this->declareRepository(3, true);
        $factory->method('getRepositoryById')->willReturnCallback(static fn(int $id) => match ($id) {
            1 => $repository_1,
            2 => $repository_2,
            3 => $repository_3,
        });

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->with(101)->willReturn($this->user);
        $this->collector = new LatestHeartbeatsCollector(
            $factory,
            $dao,
            $this->createMock(Git_GitRepositoryUrlManager::class),
            $user_manager,
        );
    }

    private function declareRepository(int $id, bool $user_can_read): GitRepository
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getId')->willReturn($id);
        $repository->method('userCanRead')->willReturn($user_can_read);
        $repository->method('getProject')->willReturn($this->project);
        $repository->method('getHTMLLink');

        return $repository;
    }

    public function testItCollectsOnlyPushesForRepositoriesUserCanView(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        self::assertCount(2, $collection->getLatestEntries());
    }

    public function testItInformsThatThereIsAtLeastOneActivityThatUserCannotRead(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        self::assertTrue($collection->areThereActivitiesUserCannotSee());
    }
}
