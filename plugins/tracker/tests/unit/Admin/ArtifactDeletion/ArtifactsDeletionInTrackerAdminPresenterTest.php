<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\ArtifactDeletion;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ArtifactsDeletionInTrackerAdminPresenter;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ArtifactsDeletionInTrackerAdminUrlBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactsDeletionInTrackerAdminPresenterTest extends TestCase
{
    private string $url;

    protected function setUp(): void
    {
        $this->url = ArtifactsDeletionInTrackerAdminUrlBuilder::fromTracker(
            TrackerTestBuilder::aTracker()->withId(10)->build()
        );
    }

    public function testDeletionIsImpossibleWhenDeletionLimitIsZero(): void
    {
        $deletion_limit = 0;
        $deletion_count = 0;

        $presenter = new ArtifactsDeletionInTrackerAdminPresenter(
            CSRFSynchronizerTokenStub::buildSelf(),
            $this->url,
            $deletion_limit,
            $deletion_count
        );

        self::assertTrue($presenter->is_deletion_impossible);
        self::assertNotEmpty($presenter->error_message);
    }

    public function testDeletionIsImpossibleWhenDeletionLimitIsExceeded(): void
    {
        $deletion_limit = 10;
        $deletion_count = 10;

        $presenter = new ArtifactsDeletionInTrackerAdminPresenter(
            CSRFSynchronizerTokenStub::buildSelf(),
            $this->url,
            $deletion_limit,
            $deletion_count
        );

        self::assertTrue($presenter->is_deletion_impossible);
        self::assertNotEmpty($presenter->error_message);
    }

    public function testDeletionIsAllowed(): void
    {
        $deletion_limit = 10;
        $deletion_count = 2;

        $presenter = new ArtifactsDeletionInTrackerAdminPresenter(
            CSRFSynchronizerTokenStub::buildSelf(),
            $this->url,
            $deletion_limit,
            $deletion_count
        );

        self::assertFalse($presenter->is_deletion_impossible);
        self::assertEmpty($presenter->error_message);
    }
}
