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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\StatusBadgeBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;
use Tuleap\User\History\HistoryEntryCollection;

final class VisitRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID                         = 101;
    private const FIRST_ARTIFACT_ID               = 1;
    private const FIRST_ARTIFACT_VISIT_TIMESTAMP  = 1584987154;
    private const SECOND_ARTIFACT_ID              = 2;
    private const SECOND_ARTIFACT_VISIT_TIMESTAMP = 1844678754;
    private \PFUser $user;
    /**
     * @var GlyphFinder&Stub
     */
    private $glyph_finder;
    /**
     * @var \Tracker_Semantic_StatusFactory&Stub
     */
    private $status_factory;
    /**
     * @var \Tracker_ArtifactFactory&Stub
     */
    private $artifact_factory;
    /**
     * @var RecentlyVisitedDao&Stub
     */
    private $recently_visited_dao;

    protected function setUp(): void
    {
        $this->user         = UserTestBuilder::buildWithId(self::USER_ID);
        $this->glyph_finder = $this->createStub(\Tuleap\Glyph\GlyphFinder::class);
        $this->glyph_finder->method('get')->willReturn($this->createStub(\Tuleap\Glyph\Glyph::class));
        $this->status_factory = $this->createStub(\Tracker_Semantic_StatusFactory::class);
        $semantic_status      = $this->createStub(\Tracker_Semantic_Status::class);
        $semantic_status->method('getField')->willReturn(null);
        $this->status_factory->method('getByTracker')->willReturn($semantic_status);
        $this->artifact_factory     = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->recently_visited_dao = $this->createStub(RecentlyVisitedDao::class);
    }

    private function getVisitHistory(HistoryEntryCollection $collection, int $max_length_history): void
    {
        $visit_retriever = new VisitRetriever(
            $this->recently_visited_dao,
            $this->artifact_factory,
            $this->glyph_finder,
            new StatusBadgeBuilder($this->status_factory),
            EventDispatcherStub::withIdentityCallback(),
        );

        $visit_retriever->getVisitHistory($collection, $max_length_history, $this->user);
    }

    public function testItRetrievesHistory(): void
    {
        $this->recently_visited_dao->method('searchVisitByUserId')->willReturn(
            [
                ['artifact_id' => self::FIRST_ARTIFACT_ID, 'created_on' => self::FIRST_ARTIFACT_VISIT_TIMESTAMP],
                ['artifact_id' => self::SECOND_ARTIFACT_ID, 'created_on' => self::SECOND_ARTIFACT_VISIT_TIMESTAMP],
            ]
        );

        $first_tracker  = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->withShortName('bug')
            ->withColor(TrackerColor::fromName('fiesta-red'))
            ->build();
        $first_artifact = ArtifactTestBuilder::anArtifact(self::FIRST_ARTIFACT_ID)
            ->inTracker($first_tracker)
            ->withTitle('Random title')
            ->build();

        $second_tracker  = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->withShortName('story')
            ->withColor(TrackerColor::fromName('deep-blue'))
            ->build();
        $second_artifact = ArtifactTestBuilder::anArtifact(self::SECOND_ARTIFACT_ID)
            ->inTracker($second_tracker)
            ->withTitle('lowland')
            ->build();

        $this->artifact_factory->method('getArtifactById')->willReturnOnConsecutiveCalls($first_artifact, $second_artifact);
        $max_length_history = 2;
        $collection         = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection, $max_length_history);

        $history = $collection->getEntries();
        $this->assertCount($max_length_history, $history);
        $this->assertSame(self::FIRST_ARTIFACT_VISIT_TIMESTAMP, $history[0]->getVisitTime());
        $this->assertSame(self::SECOND_ARTIFACT_VISIT_TIMESTAMP, $history[1]->getVisitTime());
    }

    public function testItExpectsBrokenHistory(): void
    {
        $this->recently_visited_dao->method('searchVisitByUserId')->willReturn(
            [
                ['artifact_id' => self::FIRST_ARTIFACT_ID, 'created_on' => self::FIRST_ARTIFACT_VISIT_TIMESTAMP],
                ['artifact_id' => self::SECOND_ARTIFACT_ID, 'created_on' => self::SECOND_ARTIFACT_VISIT_TIMESTAMP],
            ]
        );
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $max_length_history = 30;
        $collection         = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection, $max_length_history);

        $this->assertCount(0, $collection->getEntries());
    }
}
