<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\TrackerDeletion;

use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\TrackerDeletion\RestoreDeletedTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerRestorationControllerTest extends TestCase
{
    private RestoreDeletedTrackerStub $dao;
    private MockObject&BaseLayout $response;


    #[\Override]
    protected function setUp(): void
    {
        $this->dao      = RestoreDeletedTrackerStub::build();
        $this->response = $this->createMock(BaseLayout::class);
    }

    public function testItThrowsWhenTrackerDoesNotExist(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('tracker_id', '101')
            ->withUser(UserTestBuilder::buildSiteAdministrator())
            ->build();

        $tracker_factory = RetrieveTrackerStub::withoutTracker();
        $restorer        = new TrackerRestorationController($tracker_factory, $this->dao, CSRFSynchronizerTokenStub::buildSelf());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tracker does not exist');

        $restorer->process($request, $this->response, []);
    }

    public function testItRestoresTrackerSuccessfully(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $request = HTTPRequestBuilder::get()
            ->withParam('tracker_id', '101')
            ->withUser(UserTestBuilder::buildSiteAdministrator())
            ->build();

        $tracker_factory = RetrieveTrackerStub::withTracker($tracker);
        $restorer        = new TrackerRestorationController($tracker_factory, $this->dao, CSRFSynchronizerTokenStub::buildSelf());
        $this->response->expects($this->once())->method('addFeedback');
        $this->response->expects($this->once())->method('redirect')->with(TrackerRestorationDisplayController::FULL_URL);

        $restorer->process($request, $this->response, []);

        $this->assertEquals(1, $this->dao->getCallCount());
    }
}
