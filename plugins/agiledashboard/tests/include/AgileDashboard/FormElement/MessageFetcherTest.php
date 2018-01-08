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

namespace Tuleap\AgileDashboard\FormElement;

use TuleapTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class MessageFetcherTest extends TuleapTestCase
{
    /**
     * @var MessageFetcher
     */
    private $message_fetcher;

    public function setUp()
    {
        parent::setUp();

        $this->planning_factory       = mock('PlanningFactory');
        $this->initial_effort_factory = mock('AgileDashboard_Semantic_InitialEffortFactory');

        $this->message_fetcher = new MessageFetcher($this->planning_factory, $this->initial_effort_factory);
    }

    public function itReturnsAWarningIfTrackerIsNotAPlanningTracker()
    {
        $tracker = aMockTracker()->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($tracker)->returns(null);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($tracker);

        $this->assertArrayNotEmpty($warnings);
    }
}
