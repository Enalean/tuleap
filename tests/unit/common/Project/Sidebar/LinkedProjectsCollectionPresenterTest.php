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
final class LinkedProjectsCollectionPresenterTest extends TestCase
{
    public static function dataProviderContext(): array
    {
        return [
            'In children projects context' => [true],
            'In parent projects context'   => [false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderContext')]
    public function testItBuildsPresenterWithChildrenFromEvent(bool $is_in_children_context): void
    {
        $first_project  = ProjectTestBuilder::aProject()
            ->withUnixName('red-team')
            ->withPublicName('Red Team')
            ->build();
        $second_project = ProjectTestBuilder::aProject()
            ->withUnixName('blue-team')
            ->withPublicName('Blue Team')
            ->build();

        $searcher       = SearchLinkedProjectsStub::withValidProjects($first_project, $second_project);
        $user           = UserTestBuilder::aUser()->build();
        $source_project = ProjectTestBuilder::aProject()->build();
        $collection     = LinkedProjectsCollection::fromSourceProject(
            $searcher,
            CheckProjectAccessStub::withValidAccess(),
            $source_project,
            $user
        );

        $event = new CollectLinkedProjects($source_project, $user);
        if ($is_in_children_context) {
            $event->addChildrenProjects($collection);
        } else {
            $event->addParentProjects($collection);
        }
        $presenter = LinkedProjectsCollectionPresenter::fromEvent($event);

        self::assertSame($is_in_children_context, $presenter->is_in_children_projects_context);
        self::assertStringContainsString('2', $presenter->label);
        self::assertCount(2, $presenter->projects);
    }

    public function testItReturnsNullIfEventWasNotMutated(): void
    {
        $user           = UserTestBuilder::aUser()->build();
        $source_project = ProjectTestBuilder::aProject()->build();
        $event          = new CollectLinkedProjects($source_project, $user);

        self::assertNull(LinkedProjectsCollectionPresenter::fromEvent($event));
    }
}
