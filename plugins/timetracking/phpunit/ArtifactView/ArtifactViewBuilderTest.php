<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\ArtifactView;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use timetrackingPlugin;
use Tracker;
use Tuleap\Timetracking\Time\DateFormatter;
use Tuleap\Timetracking\Time\TimePresenterBuilder;

class ArtifactViewBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ArtifactViewBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->allows()->getId()->andReturns(101);

        $this->request = \Mockery::spy(\HTTPRequest::class);

        $project = \Mockery::spy(Project::class);
        $project->allows()->getID()->andReturns(201);

        $this->tracker = \Mockery::spy(Tracker::class);
        $this->tracker->allows()->getProject()->andReturns($project);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->artifact->allows()->getTracker()->andReturns($this->tracker);

        $this->plugin                 = \Mockery::spy(timetrackingPlugin::class);
        $this->enabler                = \Mockery::spy(\Tuleap\Timetracking\Admin\TimetrackingEnabler::class);
        $this->permissions_retriever  = \Mockery::spy(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);
        $this->time_retriever         = \Mockery::spy(\Tuleap\Timetracking\Time\TimeRetriever::class);
        $this->date_formatter         = new DateFormatter();
        $this->time_presenter_builder = new TimePresenterBuilder($this->date_formatter, \Mockery::spy(\UserManager::class));

        $this->builder = new ArtifactViewBuilder(
            $this->plugin,
            $this->enabler,
            $this->permissions_retriever,
            $this->time_retriever,
            $this->time_presenter_builder,
            $this->date_formatter
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testItBuildsTheArtifactView()
    {
        $this->plugin->allows()->isAllowed(201)->andReturns(true);
        $this->enabler->allows()->isTimetrackingEnabledForTracker($this->tracker)->andReturns(true);
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(true);
        $this->time_retriever->allows()->getTimesForUser($this->user, $this->artifact)->andReturns([]);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNotNull($view);
    }

    public function testItReturnsNullIfPluginNotAvailableForProject()
    {
        $this->plugin->allows()->isAllowed(201)->andReturns(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNull($view);
    }

    public function testItReturnsNullIfTimetrackingNotActivatedForTracker()
    {
        $this->plugin->allows()->isAllowed(201)->andReturns(true);
        $this->enabler->allows()->isTimetrackingEnabledForTracker($this->tracker)->andReturns(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNull($view);
    }

    public function testItReturnsNullIfUserIsNeitherReaderNorWriter()
    {
        $this->plugin->allows()->isAllowed(201)->andReturns(true);
        $this->enabler->allows()->isTimetrackingEnabledForTracker($this->tracker)->andReturns(true);
        $this->permissions_retriever->allows()->userCanAddTimeInTracker($this->user, $this->tracker)->andReturns(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNull($view);
    }
}
