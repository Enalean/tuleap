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

namespace Tuleap\CrossTracker\REST\v1;

use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

class CrossTrackerTestExpertQueryTest extends RestBase
{
    public function setUp()
    {
        parent::setUp();

        $this->getEpicArtifactIds();
    }

    public function testNonEmptyTitle()
    {
        $params = array(
            "trackers_id"  => array($this->epic_tracker_id),
            "expert_query" => ' @title != "" '
        );

        $response = $this->getResponse($this->client->put('cross_tracker_reports/1', null, $params));
        $this->assertEquals($response->getStatusCode(), 201);

        $cross_tracker_report = $response->json();

        $this->assertEquals(
            $cross_tracker_report["expert_query"],
            '@title != ""'
        );

        $this->allEpicArtifactsMustBeRetrievedByQuery();
    }

    private function allEpicArtifactsMustBeRetrievedByQuery()
    {
        $response = $this->getResponse($this->client->get('cross_tracker_reports/1/content?limit=50&offset=0'));

        $this->assertEquals($response->getStatusCode(), 200);
        $cross_tracker_artifacts = $response->json();

        $this->assertEquals(
            count($cross_tracker_artifacts['artifacts']),
            8
        );

        $this->assertEquals($cross_tracker_artifacts['artifacts'][0]['id'], $this->epic_artifact_ids[8]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][1]['id'], $this->epic_artifact_ids[7]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][2]['id'], $this->epic_artifact_ids[6]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][3]['id'], $this->epic_artifact_ids[5]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][4]['id'], $this->epic_artifact_ids[4]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][5]['id'], $this->epic_artifact_ids[3]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][6]['id'], $this->epic_artifact_ids[2]);
        $this->assertEquals($cross_tracker_artifacts['artifacts'][7]['id'], $this->epic_artifact_ids[1]);
    }
}
