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

use Project;
use timetrackingPlugin;
use Tuleap\Timetracking\Time\DateFormatter;
use Tuleap\Timetracking\Time\TimePresenterBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactViewBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\HTTPRequest
     */
    private $request;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var timetrackingPlugin&\PHPUnit\Framework\MockObject\MockObject
     */
    private $plugin;
    /**
     * @var \Tuleap\Timetracking\Admin\TimetrackingEnabler&\PHPUnit\Framework\MockObject\MockObject
     */
    private $enabler;
    /**
     * @var \Tuleap\Timetracking\Permissions\PermissionsRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Timetracking\Time\TimeRetriever
     */
    private $time_retriever;
    private DateFormatter $date_formatter;
    private TimePresenterBuilder $time_presenter_builder;
    private ArtifactViewBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(101);

        $this->request = $this->createMock(\HTTPRequest::class);

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(201);

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getProject')->willReturn($project);

        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->artifact->method('getId')->willReturn(101);

        $this->plugin                 = $this->createMock(timetrackingPlugin::class);
        $this->enabler                = $this->createMock(\Tuleap\Timetracking\Admin\TimetrackingEnabler::class);
        $this->permissions_retriever  = $this->createMock(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);
        $this->time_retriever         = $this->createMock(\Tuleap\Timetracking\Time\TimeRetriever::class);
        $this->date_formatter         = new DateFormatter();
        $this->time_presenter_builder = new TimePresenterBuilder($this->date_formatter, $this->createMock(\UserManager::class));

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

    public function testItBuildsTheArtifactView(): void
    {
        $this->plugin->method('isAllowed')->with(201)->willReturn(true);
        $this->enabler->method('isTimetrackingEnabledForTracker')->with($this->tracker)->willReturn(true);
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);
        $this->time_retriever->method('getTimesForUser')->with($this->user, $this->artifact)->willReturn([]);
        $this->artifact->method('getUri')->willReturn('');

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        self::assertNotNull($view);
    }

    public function testItReturnsNullIfPluginNotAvailableForProject(): void
    {
        $this->plugin->method('isAllowed')->with(201)->willReturn(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        self::assertNull($view);
    }

    public function testItReturnsNullIfTimetrackingNotActivatedForTracker(): void
    {
        $this->plugin->method('isAllowed')->with(201)->willReturn(true);
        $this->enabler->method('isTimetrackingEnabledForTracker')->with($this->tracker)->willReturn(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        self::assertNull($view);
    }

    public function testItReturnsNullIfUserIsNeitherReaderNorWriter(): void
    {
        $this->plugin->method('isAllowed')->with(201)->willReturn(true);
        $this->enabler->method('isTimetrackingEnabledForTracker')->with($this->tracker)->willReturn(true);
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(false);
        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->with($this->user, $this->tracker)->willReturn(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        self::assertNull($view);
    }
}
