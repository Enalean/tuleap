<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TrackerCreationRequestTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItIsNotValidForDuplicationWhenNoTrackerName(): void
    {
        $http_request = $this->getMockedHTTPRequest(null, null, null);

        $creation_request = new TrackerCreationRequest($http_request);

        $this->assertFalse($creation_request->areMandatoryFieldFilledForTrackerDuplication());
    }

    public function testItIsNotValidForDuplicationWhenNoTrackerShortName(): void
    {
        $http_request = $this->getMockedHTTPRequest('Bugs', null, null);

        $creation_request = new TrackerCreationRequest($http_request);

        $this->assertFalse($creation_request->areMandatoryFieldFilledForTrackerDuplication());
    }

    public function testItIsNotValidForDuplicationWhenNoTemplateId(): void
    {
        $http_request = $this->getMockedHTTPRequest('Bugs', 'tracker-bugs', null);

        $creation_request = new TrackerCreationRequest($http_request);

        $this->assertFalse($creation_request->areMandatoryFieldFilledForTrackerDuplication());
    }

    public function testItIsValidForDuplicationWhenEveryMandatoryFieldAreFilled(): void
    {
        $http_request = $this->getMockedHTTPRequest('Bugs', 'tracker-bugs', '103');

        $creation_request = new TrackerCreationRequest($http_request);

        $this->assertTrue($creation_request->areMandatoryFieldFilledForTrackerDuplication());
    }

    private function getMockedHTTPRequest(
        ?string $tracker_name,
        ?string $tracker_shortname,
        ?string $tracker_template_id
    ): \HTTPRequest {
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('tracker-name')->andReturn($tracker_name);
        $request->shouldReceive('get')->with('tracker-shortname')->andReturn($tracker_shortname);
        $request->shouldReceive('get')->with('tracker-template-id')->andReturn($tracker_template_id);
        $request->shouldReceive('get')->with('tracker-description')->andReturn(null);

        return $request;
    }
}
