<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

namespace Tuleap\CrossTracker\REST\v1;

require_once __DIR__ . '/../../bootstrap.php';

class CrossTrackerReportExtractorTest extends \TuleapTestCase
{
    private $tracker_id_1;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var \Tracker
     */
    private $tracker_1;
    /**
     * @var CrossTrackerReportExtractor
     */
    private $extractor;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function setUp()
    {
        parent::setUp();

        $this->tracker_factory = mock('TrackerFactory');
        $this->extractor       = new CrossTrackerReportExtractor($this->tracker_factory);

        $this->project      = mock('Project');
        $this->tracker_id_1 = 1;
        $this->tracker_1    = aMockTracker()->withId($this->tracker_id_1)->withProject($this->project)->build();
    }

    public function itDoesNotExtractTrackerUserCanNotView()
    {
        stub($this->tracker_factory)->getTrackerById($this->tracker_id_1)->returns($this->tracker_1);
        stub($this->tracker_1)->userCanView()->returns(false);

        $expected_result = array();
        $this->assertEqual(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }

    public function itDoesNotExtractDeletedTrackers()
    {
        stub($this->tracker_factory)->getTrackerById($this->tracker_id_1)->returns($this->tracker_1);
        stub($this->tracker_1)->userCanView()->returns(true);
        stub($this->tracker_1)->isDeleted()->returns(true);

        $expected_result = array();
        $this->assertEqual(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }

    public function itDoesNotExtractTrackerOfNonActiveProjects()
    {
        stub($this->tracker_factory)->getTrackerById($this->tracker_id_1)->returns($this->tracker_1);
        stub($this->tracker_1)->userCanView()->returns(true);
        stub($this->tracker_1)->isDeleted()->returns(false);
        stub($this->project)->isActive()->returns(false);

        $expected_result = array();
        $this->assertEqual(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }

    public function itThrowAnExceptionWhenTrackerIsNotFound()
    {
        stub($this->tracker_factory)->getTrackerById($this->tracker_id_1)->returns(null);

        $this->expectException('Tuleap\CrossTracker\REST\v1\TrackerNotFoundException');
        $this->extractor->extractTrackers(array($this->tracker_id_1));
    }

    public function itExtractTrackers()
    {
        stub($this->tracker_factory)->getTrackerById($this->tracker_id_1)->returns($this->tracker_1);
        stub($this->tracker_1)->userCanView()->returns(true);
        stub($this->tracker_1)->isDeleted()->returns(false);
        stub($this->project)->isActive()->returns(true);

        $expected_result = array($this->tracker_1);
        $this->assertEqual(
            $expected_result,
            $this->extractor->extractTrackers(array($this->tracker_id_1))
        );
    }
}
