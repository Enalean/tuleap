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
