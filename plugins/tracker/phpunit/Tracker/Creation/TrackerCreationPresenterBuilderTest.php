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

    protected function setUp(): void
    {
        $this->project_manager = \Mockery::mock(ProjectManager::class);
        $this->tracker_dao     = \Mockery::mock(TrackerDao::class);
        $this->builder         = new TrackerCreationPresenterBuilder($this->project_manager, $this->tracker_dao);
        $this->current_project = \Mockery::mock(\Project::class);

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

        $presenter = $this->builder->build($this->current_project, $this->csrf_token);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter([], $expected_list_of_existing_trackers, $this->current_project, $this->csrf_token);
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWhenRequestFails(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn(false);

        $presenter = $this->builder->build($this->current_project, $this->csrf_token);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter([], $expected_list_of_existing_trackers, $this->current_project, $this->csrf_token);
        $this->assertEquals($expected_template, $presenter);
    }

    public function testItDoesNotAddProjectsWithoutTrackers(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn([]);

        $presenter = $this->builder->build($this->current_project, $this->csrf_token);

        $expected_list_of_existing_trackers = ['names' => [], 'shortnames' => []];
        $expected_template = new TrackerCreationPresenter([], $expected_list_of_existing_trackers, $this->current_project, $this->csrf_token);
        $this->assertEquals($expected_template, $presenter);
    }


    public function testItBuildsAListOfTrackersBuildByProject(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $project->shouldReceive('getPublicName')->andReturn("My project name");
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn([$project]);
        $this->tracker_dao->shouldReceive('searchByGroupId')->andReturn(
            [
                [
                    'id'        => '1',
                    'name'      => 'bugs',
                    'item_name' => 'bugz'
                ],
                [
                    'id'        => '2',
                    'name'      => 'epics',
                    'item_name' => 'epico'
                ]
            ]
        );

        $tracker_bugs = new TrackerTemplatesRepresentation("1", "bugs");
        $tracker_epics = new TrackerTemplatesRepresentation("2", "epics");

        $project_template[] = new ProjectTemplatesRepresentation(
            $project,
            [$tracker_bugs, $tracker_epics]
        );

        $expected_list_of_existing_trackers = [
            'names'      => ['bugs', 'epics'],
            'shortnames' => ['bugz', 'epico']
        ];

        $expected_template = new TrackerCreationPresenter($project_template, $expected_list_of_existing_trackers, $this->current_project, $this->csrf_token);

        $presenter = $this->builder->build($this->current_project, $this->csrf_token);

        $this->assertEquals($expected_template, $presenter);
    }
}
