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
 */

namespace Tuleap\Tracker\REST\v1;

use Tuleap\Tracker\Report\Query\FromWhere;

require_once __DIR__.'/../bootstrap.php';

class ReportArtifactFactoryTest extends \TuleapTestCase
{
    /** @var ReportArtifactFactory */
    private $report_artifact_factory;
    /** @var \Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    public function setUp()
    {
        parent::setUp();

        $this->tracker_artifact_factory = mock('\Tracker_ArtifactFactory');

        $this->report_artifact_factory = new ReportArtifactFactory(
            $this->tracker_artifact_factory
        );
    }

    public function itReturnsAnEmptyCollectionWhenTheReportDoesNotMatchArtifacts()
    {
        $empty_report = mock('\Tracker_Report');
        $from_where   = new FromWhere('', '');

        $collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $empty_report,
            $from_where,
            10,
            0
        );

        $this->assertEqual(array(), $collection->getArtifacts());
        $this->assertEqual(0, $collection->getTotalSize());
    }

    public function itReturnsACollectionOfMatchingArtifactsCorrespondingToLimitAndOffset()
    {
        $report     = mock('\Tracker_Report');
        $from_where = new FromWhere('', '');

        stub($report)->getMatchingIdsWithAdditionalFromWhere()->returns(array('id' => '12,85,217,98'));
        $artifact_one = anArtifact()->withId(85)->build();
        $artifact_two = anArtifact()->withId(217)->build();
        stub($this->tracker_artifact_factory)->getArtifactsByArtifactIdList()->returns(
            array($artifact_one, $artifact_two)
        );

        $collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $from_where,
            2,
            1
        );

        $this->assertEqual(array($artifact_one, $artifact_two), $collection->getArtifacts());
        $this->assertEqual(4, $collection->getTotalSize());
    }
}
