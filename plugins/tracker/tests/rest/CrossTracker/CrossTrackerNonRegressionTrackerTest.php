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

namespace Tuleap\Tracker\REST\v1\CrossTracker;

use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

class CrossTrackerNonRegressionTrackerTest extends RestBase
{
    private function getResponse($request)
    {
        return $this->getResponseByToken(
            $this->getTokenForUserName(\REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testItThrowsAnExceptionWhenReportIsNotFound()
    {
        $this->setExpectedException('Guzzle\Http\Exception\ClientErrorResponseException');
        $response = $this->getResponse($this->client->get('cross_tracker_reports/100'));

        $this->assertEquals($response->getStatusCode(), 404);
    }
}
