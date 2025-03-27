<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Time;

use Tracker;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TimeDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var \Tuleap\Timetracking\Admin\AdminDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $admin_dao;
    /**
     * @var \Tuleap\Timetracking\Permissions\PermissionsRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_retriever;
    private TimeRetriever $retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao                   = $this->createMock(\Tuleap\Timetracking\Time\TimeDao::class);
        $this->admin_dao             = $this->createMock(\Tuleap\Timetracking\Admin\AdminDao::class);
        $this->permissions_retriever = $this->createMock(\Tuleap\Timetracking\Permissions\PermissionsRetriever::class);

        $this->retriever = new TimeRetriever($this->dao, $this->permissions_retriever, $this->admin_dao, \ProjectManager::instance());

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(102);

        $this->tracker  = $this->createMock(Tracker::class);
        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->tracker->method('getId')->willReturn(16);

        $this->artifact->method('getTracker')->willReturn($this->tracker);
        $this->artifact->method('getId')->willReturn(200);
    }

    public function testItReturnsAnEmptyArrayIfUserIsNotAbleToReadTimes(): void
    {
        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->with($this->user, $this->tracker)->willReturn(false);
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(false);

        $this->dao->expects(self::never())->method('getTimesAddedInArtifactByUser');
        $this->dao->expects(self::never())->method('getAllTimesAddedInArtifact');

        self::assertEmpty($this->retriever->getTimesForUser($this->user, $this->artifact));
    }

    public function testItRetrievesTimesIfTheUserIsWriter(): void
    {
        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->with($this->user, $this->tracker)->willReturn(false);
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(true);

        $this->dao->expects($this->once())->method('getTimesAddedInArtifactByUser')->with(102, 200)->willReturn([]);
        $this->dao->expects(self::never())->method('getAllTimesAddedInArtifact');

        $this->retriever->getTimesForUser($this->user, $this->artifact);
    }

    public function testItRetrievesTimesIfTheUserIsGlobalReader(): void
    {
        $this->permissions_retriever->method('userCanSeeAllTimesInTracker')->with($this->user, $this->tracker)->willReturn(true);
        $this->permissions_retriever->method('userCanAddTimeInTracker')->with($this->user, $this->tracker)->willReturn(false);

        $this->dao->expects($this->once())->method('getAllTimesAddedInArtifact')->with(200)->willReturn([]);
        $this->dao->expects(self::never())->method('getTimesAddedInArtifactByUser');

        $this->retriever->getTimesForUser($this->user, $this->artifact);
    }
}
