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
final class CollectLinkedProjectsTest extends TestCase
{
    private \Project $source_project;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->source_project = ProjectTestBuilder::aProject()->build();
        $this->user           = UserTestBuilder::aUser()->build();
    }

    private function getEvent(): CollectLinkedProjects
    {
        return new CollectLinkedProjects($this->source_project, $this->user);
    }

    public function testItDefaultsToEmptyCollections(): void
    {
        $event = $this->getEvent();
        self::assertTrue($event->getChildrenProjects()->isEmpty());
        self::assertTrue($event->getParentProjects()->isEmpty());
    }

    public static function dataProviderContext(): array
    {
        return [
            'In children projects context' => [true],
            'In parent projects context'   => [false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderContext')]
    public function testItAddsChildrenOrParentProjects(bool $is_in_children_context): void
    {
        $event          = $this->getEvent();
        $first_project  = ProjectTestBuilder::aProject()
            ->withUnixName('red-team')
            ->withPublicName('Red Team')
            ->build();
        $second_project = ProjectTestBuilder::aProject()
            ->withUnixName('blue-team')
            ->withPublicName('Blue Team')
            ->build();
        $collection     = $this->buildCollection($event, $first_project, $second_project);
        if ($is_in_children_context) {
            $event->addChildrenProjects($collection);
        } else {
            $event->addParentProjects($collection);
        }

        $context_projects       = ($is_in_children_context) ? $event->getChildrenProjects() : $event->getParentProjects();
        $other_context_projects = ($is_in_children_context) ? $event->getParentProjects() : $event->getChildrenProjects();

        self::assertFalse($context_projects->isEmpty());
        self::assertTrue($other_context_projects->isEmpty());
        self::assertCount(2, $context_projects->getProjects());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderContext')]
    public function testItThrowsWhenGivenADifferentContextThanWhatIsAlreadySet(bool $is_in_children_context): void
    {
        $event        = $this->getEvent();
        $team_project = ProjectTestBuilder::aProject()
            ->withUnixName('red-team')
            ->withPublicName('Red Team')
            ->build();
        $collection   = $this->buildCollection($event, $team_project);
        if ($is_in_children_context) {
            $event->addChildrenProjects($collection);
        } else {
            $event->addParentProjects($collection);
        }

        $program_project          = ProjectTestBuilder::aProject()
            ->withUnixName('blue-program')
            ->withPublicName('Blue Program')
            ->build();
        $other_context_collection = $this->buildCollection($event, $program_project);

        $this->expectException(CannotMixParentAndChildrenProjectsException::class);
        if ($is_in_children_context) {
            $event->addParentProjects($other_context_collection);
        } else {
            $event->addChildrenProjects($other_context_collection);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderContext')]
    public function testItMergesCollectionsWithTheSameContext(bool $is_in_children_context): void
    {
        $event            = $this->getEvent();
        $first_project    = ProjectTestBuilder::aProject()
            ->withUnixName('red-team')
            ->withPublicName('Red Team')
            ->build();
        $first_collection = $this->buildCollection($event, $first_project);
        if ($is_in_children_context) {
            $event->addChildrenProjects($first_collection);
        } else {
            $event->addParentProjects($first_collection);
        }

        $second_project    = ProjectTestBuilder::aProject()
            ->withUnixName('blue-team')
            ->withPublicName('Blue Team')
            ->build();
        $second_collection = $this->buildCollection($event, $second_project);
        if ($is_in_children_context) {
            $event->addChildrenProjects($second_collection);
        } else {
            $event->addParentProjects($second_collection);
        }

        $projects_collection = ($is_in_children_context) ? $event->getChildrenProjects() : $event->getParentProjects();

        self::assertFalse($projects_collection->isEmpty());
        self::assertCount(2, $projects_collection->getProjects());
    }

    private function buildCollection(CollectLinkedProjects $event, \Project ...$projects): LinkedProjectsCollection
    {
        $searcher       = SearchLinkedProjectsStub::withValidProjects(...$projects);
        $access_checker = CheckProjectAccessStub::withValidAccess();
        return LinkedProjectsCollection::fromSourceProject(
            $searcher,
            $access_checker,
            $event->getSourceProject(),
            $event->getCurrentUser()
        );
    }
}
