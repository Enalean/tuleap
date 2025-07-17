<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use TrackerDao;
use TrackerFactory;
use Tuleap\Color\ColorName;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerCreationPresenterBuilderTest extends TestCase
{
    private TrackerCreationPresenterBuilder $builder;
    private TrackerDao&MockObject $tracker_dao;
    private ProjectManager&MockObject $project_manager;
    private Project $current_project;
    private CSRFSynchronizerTokenInterface $csrf_token;
    private TrackerFactory&MockObject $tracker_factory;
    private PFUser&MockObject $current_user;
    private DefaultTemplatesCollectionBuilder&MockObject $default_templates_collection_builder;
    private PendingJiraImportDao&MockObject $pending_jira_dao;

    protected function setUp(): void
    {
        $this->default_templates_collection_builder = $this->createMock(DefaultTemplatesCollectionBuilder::class);

        $this->project_manager  = $this->createMock(ProjectManager::class);
        $this->tracker_dao      = $this->createMock(TrackerDao::class);
        $this->pending_jira_dao = $this->createMock(PendingJiraImportDao::class);
        $this->tracker_factory  = $this->createMock(TrackerFactory::class);

        $this->builder = new TrackerCreationPresenterBuilder(
            $this->project_manager,
            $this->tracker_dao,
            $this->pending_jira_dao,
            $this->tracker_factory,
            $this->default_templates_collection_builder,
        );

        $this->current_project = ProjectTestBuilder::aProject()->withId(104)->withUnixName('my-project-name')->withoutServices()->build();
        $this->current_user    = $this->createMock(PFUser::class);
        $this->csrf_token      = CSRFSynchronizerTokenStub::buildSelf();
    }

    public function testItReturnsAnEmptyArrayWhenPlatformHasNoProjectTemplates(): void
    {
        $this->project_manager->method('getSiteTemplates')->willReturn([]);
        $this->tracker_dao->method('searchByGroupId')->willReturn(false);
        $this->current_user->method('getProjects')->willReturn([]);

        $this->default_templates_collection_builder->expects($this->once())->method('build')
            ->willReturn(new DefaultTemplatesCollection());

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template                  = new TrackerCreationPresenter(
            [],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token,
            false
        );
        self::assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWhenRequestFails(): void
    {
        $this->project_manager->method('getSiteTemplates')->willReturn([ProjectTestBuilder::aProject()->withId(101)->build()]);
        $this->tracker_dao->method('searchByGroupId')->willReturn(false);
        $this->current_user->method('getProjects')->willReturn([]);

        $this->default_templates_collection_builder->expects($this->once())->method('build')
            ->willReturn(new DefaultTemplatesCollection());

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template                  = new TrackerCreationPresenter(
            [],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token,
            false
        );
        self::assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWithoutTrackers(): void
    {
        $this->project_manager->method('getSiteTemplates')->willReturn([ProjectTestBuilder::aProject()->withId(101)->build()]);
        $this->tracker_dao->method('searchByGroupId')->willReturn([]);
        $this->pending_jira_dao->method('searchByProjectId')->willReturn([]);
        $this->current_user->method('getProjects')->willReturn([]);

        $this->default_templates_collection_builder->expects($this->once())->method('build')
            ->willReturn(new DefaultTemplatesCollection());

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template                  = new TrackerCreationPresenter(
            [],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token,
            false
        );
        self::assertEquals($expected_template, $presenter);
    }

    public function testItBuildAListOfDefaultTemplates(): void
    {
        $this->project_manager->method('getSiteTemplates')->willReturn([ProjectTestBuilder::aProject()->withId(101)->build()]);
        $this->tracker_dao->method('searchByGroupId')->willReturn([]);
        $this->pending_jira_dao->method('searchByProjectId')->willReturn([]);
        $this->current_user->method('getProjects')->willReturn([]);

        $collection = new DefaultTemplatesCollection();
        $collection->add(
            'default-activity',
            new DefaultTemplate(
                new TrackerTemplatesRepresentation('default-activity', 'Activities', 'Description', 'fiesta-red'),
                '/path/to/xml'
            )
        );
        $collection->add(
            'default-bug',
            new DefaultTemplate(
                new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'Description', 'clockwork-orange'),
                '/path/to/xml'
            )
        );
        $this->default_templates_collection_builder->expects($this->once())->method('build')->willReturn($collection);

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template                  = new TrackerCreationPresenter(
            [
                new TrackerTemplatesRepresentation('default-activity', 'Activities', 'Description', 'fiesta-red'),
                new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'Description', 'clockwork-orange'),
            ],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token,
            false
        );
        self::assertEquals($expected_template, $presenter);
    }

    public function testItBuildsAListOfTrackersBuildByProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->withPublicName('My project name')->build();
        $this->project_manager->method('getSiteTemplates')->willReturn([$project]);

        $this->default_templates_collection_builder->expects($this->once())->method('build')
            ->willReturn(new DefaultTemplatesCollection());

        $tracker_user_not_admin = TrackerTestBuilder::aTracker()->withUserIsAdmin(false)->build();

        $tracker_user_admin = TrackerTestBuilder::aTracker()
            ->withUserIsAdmin(true)
            ->withId(4)
            ->withName('MyAwesomeTracker')
            ->withDescription('Description')
            ->withColor(ColorName::RED_WINE)
            ->build();

        $this->project_manager->method('getProject')->with('101')->willReturn($project);

        $this->current_user->method('getProjects')->willReturn(['101']);
        $this->tracker_factory->method('getTrackersByProjectIdUserCanView')->with('101', $this->current_user)
            ->willReturn([$tracker_user_not_admin, $tracker_user_admin]);

        $tracker_bugs  = new TrackerTemplatesRepresentation('1', 'request', 'Description', 'peggy-pink');
        $tracker_epics = new TrackerTemplatesRepresentation('2', 'stories', 'Description', 'sherwood-green');

        $project_template[] = new ProjectTemplatesRepresentation(
            $project,
            [$tracker_bugs, $tracker_epics]
        );

        $this->tracker_dao->method('searchByGroupId')->willReturnCallback(static fn(int|string $id) => match ((int) $id) {
            101 => [
                [
                    'id'          => '1',
                    'name'        => 'request',
                    'description' => 'Description',
                    'color'       => 'peggy-pink',
                ],
                [
                    'id'          => '2',
                    'name'        => 'stories',
                    'description' => 'Description',
                    'color'       => 'sherwood-green',
                ],
            ],
            104 => [
                [
                    'id'          => '1',
                    'name'        => 'Bugs',
                    'description' => 'Description',
                    'item_name'   => 'bugz',
                ],
                [
                    'id'          => '2',
                    'name'        => 'Epics',
                    'description' => 'Description',
                    'item_name'   => 'epico',
                ],
            ],
        });
        $this->pending_jira_dao->method('searchByProjectId')->willReturnCallback(static fn(int $id) => match ($id) {
            101 => [],
            104 => [
                [
                    'tracker_name'      => 'Pending tracker from Jira',
                    'tracker_shortname' => 'from_jira',
                ],
            ],
        });

        $expected_list_of_existing_trackers = [
            'names'      => ['bugs', 'epics', 'pending tracker from jira'],
            'shortnames' => ['bugz', 'epico', 'from_jira'],
        ];

        $trackers_from_other_projects = [
            [
                'id'       => '101',
                'name'     => 'My project name',
                'trackers' => [
                    [
                        'id'          => 4,
                        'name'        => 'MyAwesomeTracker',
                        'description' => 'Description',
                        'tlp_color'   => 'red-wine',
                    ],
                ],
            ],
        ];

        $expected_template = new TrackerCreationPresenter(
            [],
            $project_template,
            $expected_list_of_existing_trackers,
            $trackers_from_other_projects,
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token,
            false
        );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        self::assertEquals($expected_template, $presenter);
    }

    private function getTrackerColors(): array
    {
        return [
            'colors_names'  => ColorName::listValues(),
            'default_color' => ColorName::default()->value,
        ];
    }
}
