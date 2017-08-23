<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\CrossTracker;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class CrossTrackerReportFactoryTest extends \TuleapTestCase
{
    /**
     * @var \Tracker
     */
    private $tracker_2;
    /**
     * @var \Tracker
     */
    private $tracker_1;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var CrossTrackerReportFactory
     */
    private $cross_tracker_factory;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var CrossTrackerReportDao
     */
    private $report_dao;

    public function setUp()
    {
        parent::setUp();

        $this->report_dao            = mock('Tuleap\Tracker\CrossTracker\CrossTrackerReportDao');
        $this->tracker_factory       = mock('TrackerFactory');
        $this->cross_tracker_factory = new CrossTrackerReportFactory($this->report_dao, $this->tracker_factory);

        $this->user = aUser()->withId(101)->build();

        $this->tracker_1 = aMockTracker()->withId(1)->build();
        $this->tracker_2 = aMockTracker()->withId(2)->build();
    }

    public function itThrowsAnExceptionWhenReportIsNotFound()
    {
        stub($this->report_dao)->searchReportById()->returns(false);
        $this->expectException('Tuleap\Tracker\CrossTracker\CrossTrackerReportNotFoundException');

        $this->cross_tracker_factory->getById(1, $this->user);
    }

    public function itDoesNotThrowsAnExceptionWhenTrackerIsNotFound()
    {
        stub($this->report_dao)->searchReportById()->returns(
            array(1)
        );

        stub($this->report_dao)->searchReportTrackersById()->returns(
            array(
                array("tracker_id" => 1),
                array("tracker_id" => 2)
            )
        );

        stub($this->tracker_factory)->getTrackerById(1)->returns(null);
        stub($this->tracker_factory)->getTrackerById(2)->returns($this->tracker_2);

        stub($this->tracker_2)->userCanView()->returns(true);

        $expected_result = new CrossTrackerReport(1, array($this->tracker_2));

        $this->assertEqual(
            $this->cross_tracker_factory->getById(1, $this->user),
            $expected_result
        );
    }

    public function itReturnsTrackersUserCanSee()
    {
        stub($this->report_dao)->searchReportById()->returns(
            array(1)
        );

        stub($this->report_dao)->searchReportTrackersById()->returns(
            array(
                array("tracker_id" => 1),
                array("tracker_id" => 2)
            )
        );

        stub($this->tracker_factory)->getTrackerById(1)->returns($this->tracker_1);
        stub($this->tracker_factory)->getTrackerById(2)->returns($this->tracker_2);

        stub($this->tracker_1)->userCanView()->returns(false);
        stub($this->tracker_2)->userCanView()->returns(true);

        $expected_result = new CrossTrackerReport(1, array($this->tracker_2));

        $this->assertEqual(
            $this->cross_tracker_factory->getById(1, $this->user),
            $expected_result
        );
    }
}
