<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use Psr\Http\Message\ResponseInterface;
use RestBase;

final class CrossTrackerTestNonRegressionTrackerTest extends RestBase
{
    public function testItThrowsAnExceptionWhenReportIsNotFound(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'cross_tracker_reports/100'));

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenMoreThan25Trackers(): void
    {
        $params   = [
            'trackers_id' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26],
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenATrackerIsNotFoundOnePlatform(): void
    {
        $params   = [
            'trackers_id' => [1001],
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenTrackerIsDuplicateInList(): void
    {
        $params   = [
            'trackers_id' => [$this->epic_tracker_id, $this->epic_tracker_id],
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenAQueryIsDefinedAndTrackersIdAreNotAnArray(): void
    {
        $query = json_encode(
            [
                'trackers_id' => 'toto',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&report_mode=default&query=' . urlencode($query))
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenAQueryIsDefinedAndTrackersIdAreNotAnArrayOfInt(): void
    {
        $query = json_encode(
            [
                'trackers_id' => ['toto'],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&report_mode=default&query=' . urlencode($query))
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenAQueryIsDefinedAndTrackersIdAreNotSent(): void
    {
        $query = json_encode(
            ['toto']
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&report_mode=default&query=' . urlencode($query))
        );

        self::assertEquals(400, $response->getStatusCode());
    }

    public function testYouCantAccessPersonalReportOfAnOtherUser(): void
    {
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('GET', 'cross_tracker_reports/2'));

        self::assertEquals(403, $response->getStatusCode());
    }

    public function testYouCantAccessProjectReportOfProjectYouCantSee(): void
    {
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('GET', 'cross_tracker_reports/3'));

        self::assertEquals(403, $response->getStatusCode());
    }

    private function getResponseForNonProjectMember($request): ResponseInterface
    {
        return $this->getResponse($request, \REST_TestDataBuilder::TEST_USER_4_NAME);
    }
}
