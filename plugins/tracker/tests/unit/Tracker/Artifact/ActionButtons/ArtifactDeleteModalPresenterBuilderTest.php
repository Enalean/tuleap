<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\ConfigurationArtifactsDeletionStub;
use Tuleap\Tracker\Test\Stub\RetrieveUserDeletionForLastDayStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactDeleteModalPresenterBuilderTest extends TestCase
{
    public function testNullWhenUserIsNotTrackerAdmin(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withUserIsAdmin(false)->build();
        $builder = new ArtifactDeleteModalPresenterBuilder(
            $this->createStub(ArtifactDeletionCSRFSynchronizerTokenProvider::class),
            ConfigurationArtifactsDeletionStub::withLimit(10),
            RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(5),
        );

        self::assertNull($builder->getDeleteArtifactModal(
            UserTestBuilder::buildWithDefaults(),
            ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build(),
        ));
    }

    public function testNotNullWhenUserIsNotTrackerAdmin(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withUserIsAdmin(true)->build();
        $builder = new ArtifactDeleteModalPresenterBuilder(
            $this->createConfiguredMock(
                ArtifactDeletionCSRFSynchronizerTokenProvider::class,
                ['getToken' => CSRFSynchronizerTokenStub::buildSelf()],
            ),
            ConfigurationArtifactsDeletionStub::withLimit(10),
            RetrieveUserDeletionForLastDayStub::withAlreadyDoneDeletions(5),
        );

        self::assertInstanceOf(ArtifactDeleteModalPresenter::class, $builder->getDeleteArtifactModal(
            UserTestBuilder::buildWithDefaults(),
            ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build(),
        ));
    }
}
