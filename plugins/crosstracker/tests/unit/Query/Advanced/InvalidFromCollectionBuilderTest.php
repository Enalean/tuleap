<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CrossTracker\Tests\Stub\Widget\SearchCrossTrackerWidgetStub;
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Project\Sidebar\LinkedProjectsCollection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\SearchLinkedProjectsStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\From;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectIn;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerIn;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvalidFromCollectionBuilderTest extends TestCase
{
    private SearchCrossTrackerWidget $widget_retriever;
    private ProjectByIDFactory $project_factory;
    private EventDispatcherInterface $event_dispatcher;

    protected function setUp(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'user']);
        $this->project_factory  = ProjectByIDFactoryStub::buildWithoutProject();
        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();
    }

    /**
     * @return list<string>
     */
    private function getInvalidFrom(
        From $from,
    ): array {
        $in_project_checker = new WidgetInProjectChecker($this->widget_retriever);
        $builder            = new InvalidFromCollectionBuilder(
            new InvalidFromTrackerCollectorVisitor($in_project_checker),
            new InvalidFromProjectCollectorVisitor(
                $in_project_checker,
                $this->widget_retriever,
                $this->project_factory,
                $this->event_dispatcher,
            ),
            2,
        );

        return $builder->buildCollectionOfInvalidFrom($from, UserTestBuilder::buildWithDefaults())->getInvalidFrom();
    }

    public function testItRefusesUnknownFromProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('blabla', new FromProjectEqual('')), null));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase("You cannot search on 'blabla'", $result[0]);
    }

    public function testItRefusesUnknownFromTracker(): void
    {
        $result = $this->getInvalidFrom(new From(new FromTracker('blabla', new FromTrackerEqual('')), null));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase("You cannot search on 'blabla'", $result[0]);
    }

    public function testItRefusesTwoFromProject(): void
    {
        $result = $this->getInvalidFrom(new From(
            new FromProject('@project', new FromProjectEqual('self')),
            new FromProject('@project', new FromProjectEqual('self')),
        ));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase('The both conditions of \'FROM\' must be on "tracker" and "project"', $result[0]);
    }

    public function testItRefusesTwoFromTracker(): void
    {
        $result = $this->getInvalidFrom(new From(
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
        ));
        self::assertNotEmpty($result);
        self::assertStringContainsStringIgnoringCase('The both conditions of \'FROM\' must be on "tracker" and "project"', $result[0]);
    }

    public function testItReturnsEmptyForValidFrom(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project']);
        $result                 = $this->getInvalidFrom(new From(
            new FromProject('@project', new FromProjectEqual('self')),
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
        ));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenUsingProjectInSelfOutsideProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectIn(['self'])), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project with 'self' in the context of a personal dashboard", $result[0]);
    }

    public function testItReturnsErrorWhenUsingProjectInAggregatedOutsideProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectIn(['aggregated'])), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project with 'aggregated' in the context of a personal dashboard", $result[0]);
    }

    public function testItReturnsErrorWhenProjectInAggregatedInsideNormalProject(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project', 'project_id' => 101]);
        $this->project_factory  = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(101)->build());
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectIn(['self', 'aggregated'])), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project with 'aggregated' in a project without service Program enabled", $result[0]);
    }

    public function testItReturnsEmptyWhenProjectInAggregatedInProgram(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project', 'project_id' => 101]);
        $project                = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_factory  = ProjectByIDFactoryStub::buildWith($project);
        $user                   = UserTestBuilder::buildWithDefaults();
        $new_event              = new CollectLinkedProjects($project, $user);
        $collection             = LinkedProjectsCollection::fromSourceProject(
            SearchLinkedProjectsStub::withValidProjects($project),
            CheckProjectAccessStub::withValidAccess(),
            $project,
            $user,
        );
        $new_event->addChildrenProjects($collection);
        $this->event_dispatcher = EventDispatcherStub::withCallback(static fn(object $event) => match ($event::class) {
            CollectLinkedProjects::class => $new_event,
            default                      => $event,
        });
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectIn(['self', 'aggregated'])), null));
        self::assertEmpty($result);
    }

    public function testItReturnsEmptyWhenProjectInSelfInsideProject(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project']);
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectIn(['self'])), null));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenProjectSelfOutsideProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('self')), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project with 'self' in the context of a personal dashboard", $result[0]);
    }

    public function testItReturnsEmptyWhenProjectSelfInsideProject(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project']);
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('self')), null));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenProjectAggregatedOutsideProject(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('aggregated')), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project with 'aggregated' in the context of a personal dashboard", $result[0]);
    }

    public function testItReturnsErrorWhenProjectAggregatedInsideNormalProject(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project', 'project_id' => 101]);
        $this->project_factory  = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(101)->build());
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('aggregated')), null));
        self::assertCount(1, $result);
        self::assertEquals("You cannot use @project with 'aggregated' in a project without service Program enabled", $result[0]);
    }

    public function testItReturnsErrorWhenProjectAggregatedInsideTeamProject(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project', 'project_id' => 101]);
        $project                = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_factory  = ProjectByIDFactoryStub::buildWith($project);
        $user                   = UserTestBuilder::buildWithDefaults();
        $new_event              = new CollectLinkedProjects($project, $user);
        $collection             = LinkedProjectsCollection::fromSourceProject(
            SearchLinkedProjectsStub::withValidProjects($project),
            CheckProjectAccessStub::withValidAccess(),
            $project,
            $user,
        );
        $new_event->addParentProjects($collection);
        $this->event_dispatcher = EventDispatcherStub::withCallback(static fn(object $event) => match ($event::class) {
            CollectLinkedProjects::class => $new_event,
            default                      => $event,
        });
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('aggregated')), null));
        self::assertCount(1, $result);
        self::assertEquals("You can use @project with 'aggregated' only in a Program project", $result[0]);
    }

    public function testItReturnsEmptyWhenProjectAggregatedInsideProgramProject(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project', 'project_id' => 101]);
        $project                = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_factory  = ProjectByIDFactoryStub::buildWith($project);
        $user                   = UserTestBuilder::buildWithDefaults();
        $new_event              = new CollectLinkedProjects($project, $user);
        $collection             = LinkedProjectsCollection::fromSourceProject(
            SearchLinkedProjectsStub::withValidProjects($project),
            CheckProjectAccessStub::withValidAccess(),
            $project,
            $user,
        );
        $new_event->addChildrenProjects($collection);
        $this->event_dispatcher = EventDispatcherStub::withCallback(static fn(object $event) => match ($event::class) {
            CollectLinkedProjects::class => $new_event,
            default                      => $event,
        });
        $result                 = $this->getInvalidFrom(new From(new FromProject('@project', new FromProjectEqual('aggregated')), null));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenProjectNameEqualEmpty(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project.name', new FromProjectEqual('')), null));
        self::assertCount(1, $result);
        self::assertEquals('@project.name cannot be empty', $result[0]);
    }

    public function testItReturnsErrorWhenProjectNameInEmpty(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project.name', new FromProjectIn(['My project', ''])), null));
        self::assertCount(1, $result);
        self::assertEquals('@project.name cannot be empty', $result[0]);
    }

    public function testItReturnsEmptyWhenProjectNameIsValid(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project.name', new FromProjectEqual('Project name')), null));
        self::assertEmpty($result);

        $result = $this->getInvalidFrom(new From(new FromProject('@project.name', new FromProjectIn(['My project', 'Awesome project'])), null));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenProjectCategoryEqualEmpty(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project.category', new FromProjectEqual('')), null));
        self::assertCount(1, $result);
        self::assertEquals('@project.category cannot be empty', $result[0]);
    }

    public function testItReturnsErrorWhenProjectCategoryInEmpty(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project.category', new FromProjectIn(['some', '', 'empty'])), null));
        self::assertCount(1, $result);
        self::assertEquals('@project.category cannot be empty', $result[0]);
    }

    public function testItReturnsEmptyWhenProjectCategoryIsValid(): void
    {
        $result = $this->getInvalidFrom(new From(new FromProject('@project.category', new FromProjectEqual('some')), null));
        self::assertEmpty($result);

        $result = $this->getInvalidFrom(new From(new FromProject('@project.category', new FromProjectIn(['some', 'value'])), null));
        self::assertEmpty($result);
    }

    public function testItReturnsErrorWhenTrackerNameOutsideProjectWithoutProjectCondition(): void
    {
        $result = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerEqual('release')), null));
        self::assertCount(1, $result);
        self::assertEquals('In the context of a personal dashboard, you must provide a @project condition in the FROM part of your query', $result[0]);

        $result = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerIn(['release'])), null));
        self::assertCount(1, $result);
        self::assertEquals('In the context of a personal dashboard, you must provide a @project condition in the FROM part of your query', $result[0]);
    }

    public function testItReturnsEmptyWhenTrackerNameWithProjectCondition(): void
    {
        self::assertEmpty($this->getInvalidFrom(new From(
            new FromTracker('@tracker.name', new FromTrackerEqual('release')),
            new FromProject('@project.category', new FromProjectEqual('some')),
        )));
        self::assertEmpty($this->getInvalidFrom(new From(
            new FromTracker('@tracker.name', new FromTrackerIn(['release'])),
            new FromProject('@project.category', new FromProjectEqual('some')),
        )));
    }

    public function testItReturnsErrorWhenTrackerNameIsEmpty(): void
    {
        $this->widget_retriever = SearchCrossTrackerWidgetStub::withExistingWidget(['dashboard_type' => 'project']);
        $result                 = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerEqual('')), null));
        self::assertCount(1, $result);
        self::assertEquals('@tracker.name cannot be empty', $result[0]);

        $result = $this->getInvalidFrom(new From(new FromTracker('@tracker.name', new FromTrackerIn(['release', '', 'sprint'])), null));
        self::assertCount(1, $result);
        self::assertEquals('@tracker.name cannot be empty', $result[0]);
    }
}
