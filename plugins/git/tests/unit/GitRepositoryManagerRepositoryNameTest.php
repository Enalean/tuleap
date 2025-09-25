<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use EventManager;
use Git_SystemEventManager;
use GitDao;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectHistoryDao;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\SystemEvent\OngoingDeletionDAO;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryManagerRepositoryNameTest extends TestCase
{
    private GitRepositoryFactory&MockObject $factory;
    private Project $project;
    private GitRepositoryManager $manager;
    private string $project_name;

    #[\Override]
    protected function setUp(): void
    {
        $project_id         = 12;
        $this->project_name = 'garden';
        $this->project      = ProjectTestBuilder::aProject()->withId($project_id)->withUnixName($this->project_name)->build();

        $this->factory = $this->createMock(GitRepositoryFactory::class);
        $this->manager = new GitRepositoryManager(
            $this->factory,
            $this->createMock(Git_SystemEventManager::class),
            $this->createMock(GitDao::class),
            '/tmp/',
            $this->createMock(FineGrainedPermissionReplicator::class),
            $this->createMock(ProjectHistoryDao::class),
            $this->createMock(HistoryValueFormatter::class),
            $this->createMock(EventManager::class),
            $this->createMock(OngoingDeletionDAO::class),
        );
    }

    private function aRepoWithPath($path): GitRepository
    {
        return GitRepositoryTestBuilder::aProjectRepository()
            ->inProject($this->project)
            ->withPath($this->project_name . '/' . $path . '.git')
            ->build();
    }

    public function testItCannotCreateARepositoryWithSamePath(): void
    {
        $this->factory->method('getAllRepositories')->with($this->project)->willReturn([$this->aRepoWithPath('bla')]);
        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function testItCannotCreateARepositoryWithSamePathThatIsNotAtRoot(): void
    {
        $this->factory->method('getAllRepositories')->with($this->project)->willReturn([$this->aRepoWithPath('foo/bla')]);
        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla')));
    }

    public function testItForbidCreationOfRepositoriesWhenPathAlreadyExists(): void
    {
        $this->factory->method('getAllRepositories')->with($this->project)->willReturn([$this->aRepoWithPath('bla')]);

        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum')));
        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum/zaz')));

        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla')));
        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla/top')));
        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('blafoo')));
    }

    public function testItForbidCreationOfRepositoriesWhenPathAlreadyExistsAndHasParents(): void
    {
        $this->factory->method('getAllRepositories')->with($this->project)->willReturn([$this->aRepoWithPath('foo/bla')]);

        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff')));
        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff/zaz')));

        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));
        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/foo')));
        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function testItForbidCreationWhenNewRepoIsInsideExistingPath(): void
    {
        $this->factory->method('getAllRepositories')->with($this->project)->willReturn([$this->aRepoWithPath('foo/bar/bla')]);

        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo')));
        self::assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));

        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar/zorg')));
        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/zorg')));
        self::assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foobar/zorg')));
    }
}
