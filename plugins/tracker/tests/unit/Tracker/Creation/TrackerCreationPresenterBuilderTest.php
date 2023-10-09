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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectManager;
use TrackerDao;
use Tuleap\Tracker\Creation\JiraImporter\JiraRunner;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\TrackerColor;

final class TrackerCreationPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PendingJiraImportDao
     */
    private $pending_jira_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|JiraRunner
     */
    private $jira_runner;

    protected function setUp(): void
    {
        $this->default_templates_collection_builder = \Mockery::mock(DefaultTemplatesCollectionBuilder::class);

        $this->project_manager  = \Mockery::mock(ProjectManager::class);
        $this->tracker_dao      = \Mockery::mock(TrackerDao::class);
        $this->pending_jira_dao = \Mockery::mock(PendingJiraImportDao::class);
        $this->tracker_factory  = \Mockery::mock(\TrackerFactory::class);
        $this->jira_runner      = \Mockery::mock(JiraRunner::class);

        $this->builder = new TrackerCreationPresenterBuilder(
            $this->project_manager,
            $this->tracker_dao,
            $this->pending_jira_dao,
            $this->tracker_factory,
            $this->default_templates_collection_builder,
            $this->jira_runner
        );

        $this->current_project = \Mockery::mock(\Project::class);
        $this->current_user    = \Mockery::mock(\PFUser::class);

        $this->current_project->shouldReceive('getUnixNameLowerCase')->andReturn('my-project-name');
        $this->current_project->shouldReceive('getID')->andReturn(104);
        $this->current_project->shouldReceive('usesService')->with('tracker')->andReturn(false);

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

        $this->jira_runner->shouldReceive(['canBeProcessedAsynchronously' => true]);

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
            true,
            false
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

        $this->jira_runner->shouldReceive(['canBeProcessedAsynchronously' => true]);

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
            true,
            false
        );
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWithoutTrackers(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn([]);
        $this->pending_jira_dao->shouldReceive('searchByProjectId')->andReturn([]);
        $this->current_user->shouldReceive('getProjects')->andReturn([]);

        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                new DefaultTemplatesCollection([])
            );

        $this->jira_runner->shouldReceive(['canBeProcessedAsynchronously' => true]);

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
            true,
            false
        );
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItBuildAListOfDefaultTemplates(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn([]);
        $this->pending_jira_dao->shouldReceive('searchByProjectId')->andReturn([]);
        $this->current_user->shouldReceive('getProjects')->andReturn([]);

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
        $this->default_templates_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn(
                $collection
            );

        $this->jira_runner->shouldReceive(['canBeProcessedAsynchronously' => true]);

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
            true,
            false
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
            ]
        );
        $this->pending_jira_dao->shouldReceive('searchByProjectId')->with(101)->andReturn([]);

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
        $tracker_user_admin->shouldReceive('getDescription')->andReturn('Description');
        $tracker_user_admin->shouldReceive('getColor')->andReturn(TrackerColor::fromName('red-wine'));

        $this->project_manager->shouldReceive('getProject')->with('101')->andReturn($project);

        $this->current_user->shouldReceive('getProjects')->andReturn(['101']);
        $this->tracker_factory->shouldReceive('getTrackersByProjectIdUserCanView')
            ->with('101', $this->current_user)
            ->andReturn([
                $tracker_user_not_admin,
                $tracker_user_admin,
            ]);

        $tracker_bugs  = new TrackerTemplatesRepresentation('1', 'request', 'Description', 'peggy-pink');
        $tracker_epics = new TrackerTemplatesRepresentation('2', 'stories', 'Description', 'sherwood-green');

        $project_template[] = new ProjectTemplatesRepresentation(
            $project,
            [$tracker_bugs, $tracker_epics]
        );

        $this->tracker_dao->shouldReceive('searchByGroupId')->with(104)->andReturn(
            [
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
            ]
        );
        $this->pending_jira_dao->shouldReceive('searchByProjectId')->with(104)->andReturn(
            [
                [
                    'tracker_name'      => 'Pending tracker from Jira',
                    'tracker_shortname' => 'from_jira',
                ],
            ]
        );

        $expected_list_of_existing_trackers = [
            'names'      => ['bugs', 'epics', 'pending tracker from jira'],
            'shortnames' => ['bugz', 'epico', 'from_jira'],
        ];

        $trackers_from_other_projects = [
            [
                'id' => '101',
                'name' => 'My project name',
                'trackers' => [
                    [
                        'id' => 4,
                        'name' => 'MyAwesomeTracker',
                        'description' => 'Description',
                        'tlp_color' => 'red-wine',
                    ],
                ],
            ],
        ];

        $this->jira_runner->shouldReceive(['canBeProcessedAsynchronously' => true]);

        $expected_template = new TrackerCreationPresenter(
            [],
            $project_template,
            $expected_list_of_existing_trackers,
            $trackers_from_other_projects,
            $this->getTrackerColors(),
            $this->current_project,
            $this->csrf_token,
            true,
            false
        );

        $presenter = $this->builder->build($this->current_project, $this->csrf_token, $this->current_user);

        $this->assertEquals($expected_template, $presenter);
    }

    private function getTrackerColors(): array
    {
        return [
            'colors_names' => TrackerColor::COLOR_NAMES,
            'default_color' => TrackerColor::default()->getName(),
        ];
    }
}
