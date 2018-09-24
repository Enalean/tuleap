<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_ChangesetFactory;

class Tracker_ArtifactTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    public function testLastChangesetIsRetrieved()
    {
        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changeset_factory = \Mockery::mock(Tracker_Artifact_ChangesetFactory::class);
        $changeset_factory->shouldReceive('getLastChangeset')->once()->andReturns($changeset);

        $artifact->shouldReceive('getChangesetFactory')->once()->andReturns($changeset_factory);

        $this->assertSame($changeset, $artifact->getLastChangeset());
        $this->assertSame($changeset, $artifact->getLastChangeset());
    }

    public function testLastChangesetIsRetrievedWhenAllChangesetsHaveAlreadyBeenLoaded()
    {
        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial();

        $last_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changesets = [
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            $last_changeset
        ];

        $artifact->setChangesets($changesets);
        $artifact->shouldReceive('getChangesets')->once()->andReturns($changesets);

        $this->assertSame($last_changeset, $artifact->getLastChangeset());
        $this->assertSame($last_changeset, $artifact->getLastChangeset());
    }
}
