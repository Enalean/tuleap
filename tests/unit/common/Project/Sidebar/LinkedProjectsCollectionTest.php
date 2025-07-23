<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Sidebar;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\Test\Stubs\SearchLinkedProjectsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkedProjectsCollectionTest extends TestCase
{
    private CheckProjectAccessStub $access_checker;
    private \Project $source_project;
    private \PFUser $user;
    private \Project $first_linked_project;
    private \Project $second_linked_project;
    private SearchLinkedProjectsStub $searcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->access_checker        = CheckProjectAccessStub::withValidAccess();
        $this->source_project        = ProjectTestBuilder::aProject()->build();
        $this->user                  = UserTestBuilder::aUser()->build();
        $this->first_linked_project  = ProjectTestBuilder::aProject()
            ->withUnixName('first-project')
            ->withPublicName('First Project')
            ->build();
        $this->second_linked_project = ProjectTestBuilder::aProject()
            ->withUnixName('second-project')
            ->withPublicName('Second Project')
            ->build();
        $this->searcher              = SearchLinkedProjectsStub::withValidProjects(
            $this->first_linked_project,
            $this->second_linked_project
        );
    }

    public function testItBuildsAListOfLinkedProjectsFromASourceProject(): void
    {
        $collection = LinkedProjectsCollection::fromSourceProject(
            $this->searcher,
            $this->access_checker,
            $this->source_project,
            $this->user
        );
        self::assertFalse($collection->isEmpty());
        $linked_projects = $collection->getProjects();
        self::assertCount(2, $linked_projects);
        self::assertContainsEquals(
            LinkedProject::fromProject($this->access_checker, $this->first_linked_project, $this->user),
            $linked_projects
        );
        self::assertContainsEquals(
            LinkedProject::fromProject($this->access_checker, $this->second_linked_project, $this->user),
            $linked_projects
        );
    }

    public function testItReturnsEmptyCollectionWhenNoLinkedProjects(): void
    {
        $searcher = SearchLinkedProjectsStub::withValidProjects();

        $collection = LinkedProjectsCollection::fromSourceProject(
            $searcher,
            $this->access_checker,
            $this->source_project,
            $this->user
        );
        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection->getProjects());
    }

    public function testItReturnsEmptyCollectionWhenUserCannotAccessAnyLinkedProject(): void
    {
        $this->access_checker = CheckProjectAccessStub::withPrivateProjectWithoutAccess();

        $collection = LinkedProjectsCollection::fromSourceProject(
            $this->searcher,
            $this->access_checker,
            $this->source_project,
            $this->user
        );
        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection->getProjects());
    }

    public function testItMergesWithAnotherCollection(): void
    {
        $first_collection = LinkedProjectsCollection::fromSourceProject(
            $this->searcher,
            $this->access_checker,
            $this->source_project,
            $this->user
        );

        $third_linked_project  = ProjectTestBuilder::aProject()
            ->withUnixName('third-project')
            ->withPublicName('Third Project')
            ->build();
        $fourth_linked_project = ProjectTestBuilder::aProject()
            ->withUnixName('fourth-project')
            ->withPublicName('Fourth Project')
            ->build();
        $this->searcher        = SearchLinkedProjectsStub::withValidProjects(
            $third_linked_project,
            $fourth_linked_project
        );

        $second_collection = LinkedProjectsCollection::fromSourceProject(
            $this->searcher,
            $this->access_checker,
            $this->source_project,
            $this->user
        );

        $third_collection = $first_collection->merge($second_collection);
        self::assertNotSame($third_collection, $first_collection);
        self::assertFalse($third_collection->isEmpty());
        $linked_projects = $third_collection->getProjects();
        self::assertCount(4, $linked_projects);
        self::assertContainsEquals(
            LinkedProject::fromProject($this->access_checker, $third_linked_project, $this->user),
            $linked_projects
        );
        self::assertContainsEquals(
            LinkedProject::fromProject($this->access_checker, $fourth_linked_project, $this->user),
            $linked_projects
        );
    }

    public function testTwoEmptyCollectionsMergedAreStillEmpty(): void
    {
        $first_empty  = LinkedProjectsCollection::buildEmpty();
        $second_empty = LinkedProjectsCollection::buildEmpty();

        $collection = $second_empty->merge($first_empty);
        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection->getProjects());
    }

    public function testMergeSortArrays(): void
    {
        $source_project = ProjectTestBuilder::aProject()->build();

        $b_project        = ProjectTestBuilder::aProject()
            ->withUnixName('b-project')
            ->withPublicName('B Project')
            ->build();
        $searcher         = SearchLinkedProjectsStub::withValidProjects(
            $b_project
        );
        $first_collection = LinkedProjectsCollection::fromSourceProject(
            $searcher,
            $this->access_checker,
            $source_project,
            $this->user
        );

        $a_project         = ProjectTestBuilder::aProject()
            ->withUnixName('a-project')
            ->withPublicName('a Project')
            ->build();
        $searcher          = SearchLinkedProjectsStub::withValidProjects(
            $a_project
        );
        $second_collection = LinkedProjectsCollection::fromSourceProject(
            $searcher,
            $this->access_checker,
            $source_project,
            $this->user
        );

        $collection      = $second_collection->merge($first_collection);
        $linked_projects = $collection->getProjects();

        self::assertCount(2, $linked_projects);
        self::assertEquals(
            LinkedProject::fromProject($this->access_checker, $a_project, $this->user),
            $linked_projects[0]
        );
        self::assertEquals(
            LinkedProject::fromProject($this->access_checker, $b_project, $this->user),
            $linked_projects[1]
        );
    }
}
