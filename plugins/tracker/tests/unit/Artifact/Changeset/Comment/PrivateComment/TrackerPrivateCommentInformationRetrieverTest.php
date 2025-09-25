<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerPrivateCommentInformationRetrieverTest extends TestCase
{
    private TrackerPrivateCommentUGroupEnabledDao&MockObject $tracker_private_comment_ugroup_enabled_dao;
    private TrackerPrivateCommentInformationRetriever $retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_private_comment_ugroup_enabled_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->retriever                                  = new TrackerPrivateCommentInformationRetriever($this->tracker_private_comment_ugroup_enabled_dao);
    }

    public function testRetrievesInformation(): void
    {
        $this->tracker_private_comment_ugroup_enabled_dao->method('isTrackerEnabledPrivateComment')->willReturn(true);

        self::assertTrue($this->retriever->doesTrackerAllowPrivateComments(TrackerTestBuilder::aTracker()->build()));
    }
}
