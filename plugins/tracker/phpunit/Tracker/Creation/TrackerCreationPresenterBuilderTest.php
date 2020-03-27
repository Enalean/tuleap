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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use TrackerDao;
use Tuleap\Tracker\TrackerColor;

final class TrackerCreationPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerCreationPresenterBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerDao
     */
    private $tracker_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var \Project|\Mockery\MockInterface|\Project
     */
    private $current_project;

    /**
     * @var \CSRFSynchronizerToken|\Mockery\MockInterface|\CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var \TrackerFactory|\Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var \PFUser|\Mockery\MockInterface|\PFUser
     */
    private $current_user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DefaultTemplatesCollection
     */
    private $default_templates_collection_builder;

    protected function setUp(): void
    {
        $this->default_templates_collection_builder = \Mockery::mock(DefaultTemplatesCollectionBuilder::class);

        $this->project_manager = \Mockery::mock(ProjectManager::class);
        $this->tracker_dao     = \Mockery::mock(TrackerDao::class);
        $this->tracker_factory = \Mockery::mock(\TrackerFactory::class);
        $this->builder         = new TrackerCreationPresenterBuilder(
            $this->project_manager,
            $this->tracker_dao,
            $this->tracker_factory,
            $this->default_templates_collection_builder
        );

        $this->current_project = \Mockery::mock(\Project::class);
        $this->current_user    = \Mockery::mock(\PFUser::class);

        $this->current_project->shouldReceive('getUnixNameLowerCase')->andReturn('my-project-name');
        $this->current_project->shouldReceive('getID')->andReturn(104);

        $this->csrf_token = \Mockery::mock(\CSRFSynchronizerToken::class);

        $this->csrf_token->shouldReceive('getTokenName')->andReturn('challenge');
        $this->csrf_token->shouldReceive('getToken')->andReturn('12345abcdef');
    }

    public function testItReturnsAnEmptyArrayWhenPlatformHasNoProjectTemplates(): void
    {
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn(false);
        $this->current_user->shouldReceive('getProjects')->andReturn([]);

        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                new DefaultTemplatesCollection([])
            );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter(
            [],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token
        );
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWhenRequestFails(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn(false);
        $this->current_user->shouldReceive('getProjects')->andReturn([]);

        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                new DefaultTemplatesCollection([])
            );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter(
            [],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token
        );
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWithoutTrackers(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn([]);
        $this->current_user->shouldReceive('getProjects')->andReturn([]);

        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                new DefaultTemplatesCollection([])
            );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter(
            [],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token
        );
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItBuildAListOfDefaultTemplates(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn([]);
        $this->current_user->shouldReceive('getProjects')->andReturn([]);

        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                new DefaultTemplatesCollection([
                    'default-activity' => new DefaultTemplate(
                        new TrackerTemplatesRepresentation('default-activity', 'Activities', 'fiesta-red'),
                        '/path/to/xml'
                    ),
                    'default-bug' => new DefaultTemplate(
                        new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'clockwork-orange'),
                        '/path/to/xml'
                    )
                ])
            );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter(
            [
                new TrackerTemplatesRepresentation('default-activity', 'Activities', 'fiesta-red'),
                new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'clockwork-orange')
            ],
            [],
            $expected_list_of_existing_trackers,
            [],
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token
        );
        $this->assertEquals($expected_template, $presenter);
    }


    public function testItBuildsAListOfTrackersBuildByProject(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $project->shouldReceive('getPublicName')->andReturn('My project name');
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->with(101)->andReturn(
            [
                [
                    'id'        => '1',
                    'name'      => 'request',
                    'color'     => 'peggy-pink'
                ],
                [
                    'id'        => '2',
                    'name'      => 'stories',
                    'color'     => 'sherwood-green'
                ]
            ]
        );

        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                new DefaultTemplatesCollection([])
            );

        $tracker_user_not_admin = \Mockery::mock(\Tracker::class);
        $tracker_user_not_admin->shouldReceive('userIsAdmin')->andReturn(false);

        $tracker_user_admin = \Mockery::mock(\Tracker::class);
        $tracker_user_admin->shouldReceive('userIsAdmin')->andReturn(true);
        $tracker_user_admin->shouldReceive('getId')->andReturn('4');
        $tracker_user_admin->shouldReceive('getName')->andReturn('MyAwesomeTracker');
        $tracker_user_admin->shouldReceive('getColor')->andReturn(TrackerColor::fromName('red-wine'));

        $this->project_manager->shouldReceive('getProject')->with('101')->andReturn($project);

        $this->current_user->shouldReceive('getProjects')->andReturn(['101']);
        $this->tracker_factory->shouldReceive('getTrackersByGroupIdUserCanView')
            ->with('101', $this->current_user)
            ->andReturn([
                $tracker_user_not_admin,
                $tracker_user_admin
            ]);

        $tracker_bugs = new TrackerTemplatesRepresentation('1', 'request', 'peggy-pink');
        $tracker_epics = new TrackerTemplatesRepresentation('2', 'stories', 'sherwood-green');

        $project_template[] = new ProjectTemplatesRepresentation(
            $project,
            [$tracker_bugs, $tracker_epics]
        );

        $this->tracker_dao->shouldReceive('searchByGroupId')->with(104)->andReturn(
            [
                [
                    'id'        => '1',
                    'name'      => 'Bugs',
                    'item_name' => 'bugz'
                ],
                [
                    'id'        => '2',
                    'name'      => 'Epics',
                    'item_name' => 'epico'
                ]
            ]
        );

        $expected_list_of_existing_trackers = [
            'names'      => ['bugs', 'epics'],
            'shortnames' => ['bugz', 'epico']
        ];

        $trackers_from_other_projects = [
            [
                'id' => '101',
                'name' => 'My project name',
                'trackers' => [
                    [
                        'id' => 4,
                        'name' => 'MyAwesomeTracker',
                        'tlp_color' => 'red-wine'
                    ]
                ]
            ]
        ];

        $expected_template = new TrackerCreationPresenter(
            [],
            $project_template,
            $expected_list_of_existing_trackers,
            $trackers_from_other_projects,
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token
        );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $this->assertEquals($expected_template, $presenter);
    }

    private function getTrackerColors(): array
    {
        return [
            'colors_names' => TrackerColor::COLOR_NAMES,
            'default_color' => TrackerColor::default()->getName()
        ];
    }
}
