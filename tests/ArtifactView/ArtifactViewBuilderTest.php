<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timesheeting\ArtifactView;

use Tuleap\Timesheeting\Time\DateFormatter;
use Tuleap\Timesheeting\Time\TimePresenterBuilder;
use TuleapTestCase;

require_once __DIR__.'/../bootstrap.php';

class ArtifactViewBuilderTest extends TuleapTestCase
{
    /**
     * @var ArtifactViewBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->user     = aUser()->withId(101)->build();
        $this->request  = aRequest()->build();

        $project        = aMockProject()->withId(201)->build();
        $this->tracker  = aMockTracker()->withProject($project)->build();
        $this->artifact = aMockArtifact()->withTracker($this->tracker)->build();

        $this->plugin                 = mock('timesheetingPlugin');
        $this->enabler                = mock('Tuleap\Timesheeting\Admin\TimesheetingEnabler');
        $this->permissions_retriever  = mock('Tuleap\Timesheeting\Permissions\PermissionsRetriever');
        $this->time_retriever         = mock('Tuleap\Timesheeting\Time\TimeRetriever');
        $this->date_formatter         = new DateFormatter();
        $this->time_presenter_builder = new TimePresenterBuilder($this->date_formatter);

        $this->builder = new ArtifactViewBuilder(
            $this->plugin,
            $this->enabler,
            $this->permissions_retriever,
            $this->time_retriever,
            $this->time_presenter_builder,
            $this->date_formatter
        );
    }

    public function itBuildsTheArtifactView()
    {
        stub($this->plugin)->isAllowed(201)->returns(true);
        stub($this->enabler)->isTimesheetingEnabledForTracker($this->tracker)->returns(true);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(true);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(true);
        stub($this->time_retriever)->getTimesForUser($this->user, $this->artifact)->returns(array());

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNotNull($view);
    }

    public function itReturnsNullIfPluginNotAvailableForProject()
    {
        stub($this->plugin)->isAllowed(201)->returns(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNull($view);
    }

    public function itReturnsNullIfTimesheetingNotActivatedForTracker()
    {
        stub($this->plugin)->isAllowed(201)->returns(true);
        stub($this->enabler)->isTimesheetingEnabledForTracker($this->tracker)->returns(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNull($view);
    }

    public function itReturnsNullIfUserIsNeitherReaderNorWriter()
    {
        stub($this->plugin)->isAllowed(201)->returns(true);
        stub($this->enabler)->isTimesheetingEnabledForTracker($this->tracker)->returns(true);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(false);
        stub($this->permissions_retriever)->userCanAddTimeInTracker($this->user, $this->tracker)->returns(false);

        $view = $this->builder->build($this->user, $this->request, $this->artifact);

        $this->assertNull($view);
    }
}
