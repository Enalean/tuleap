<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use PFUser;
use Project;
use TestHelper;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\Project\HeartbeatsEntry;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LatestHeartbeatsCollectorTest extends TestCase
{
    private LatestHeartbeatsCollector $collector;
    private Project $project;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->user    = UserTestBuilder::aUser()->build();

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchLatestUpdatedArtifactsInProject')
            ->with(101, HeartbeatsEntryCollection::NB_MAX_ENTRIES)
            ->willReturn(TestHelper::arrayToDar(['id' => 1], ['id' => 2], ['id' => 3]));

        $tracker   = TrackerTestBuilder::aTracker()->withColor(TrackerColor::default())->build();
        $artifact1 = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($tracker)
            ->userCanView($this->user)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->submittedOn(1272553678)->build())
            ->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)
            ->inTracker($tracker)
            ->userCannotView($this->user)
            ->withChangesets(ChangesetTestBuilder::aChangeset(2)->submittedOn(1425343153)->build())
            ->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(3)
            ->inTracker($tracker)
            ->userCanView($this->user)
            ->withChangesets(ChangesetTestBuilder::aChangeset(3)->submittedOn(1525085316)->build())
            ->build();

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')->willReturnCallback(static fn(array $row) => match ((int) $row['id']) {
            1 => $artifact1,
            2 => $artifact2,
            3 => $artifact3,
        });

        $this->collector = new LatestHeartbeatsCollector(
            $dao,
            $factory,
            RetrieveUserByIdStub::withUser($this->user),
            EventDispatcherStub::withIdentityCallback()
        );
    }

    public function testItConvertsArtifactsIntoHeartbeats(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $entries = $collection->getLatestEntries();
        foreach ($entries as $entry) {
            self::assertInstanceOf(HeartbeatsEntry::class, $entry);
        }
    }

    public function testItCollectsOnlyArtifactsUserCanView(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        self::assertCount(2, $collection->getLatestEntries());
    }

    public function testItInformsThatThereIsAtLeastOneActivityThatUserCannotRead(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        self::assertTrue($collection->areThereActivitiesUserCannotSee());
    }
}
