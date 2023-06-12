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
use Tuleap\Glyph\Glyph;
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
    private const USER_ID = 101;

    private const FIRST_ARTIFACT_ID              = 1;
    private const FIRST_ARTIFACT_VISIT_TIMESTAMP = 1584987154;
    private const FIRST_ARTIFACT_TITLE           = 'Random title';
    private const FIRST_TRACKER_COLOR            = 'fiesta-red';
    private const FIRST_TRACKER_SHORTNAME        = 'bug';

    private const SECOND_ARTIFACT_ID              = 2;
    private const SECOND_ARTIFACT_VISIT_TIMESTAMP = 1844678754;
    private const SECOND_ARTIFACT_TITLE           = 'lowland';
    private const SECOND_TRACKER_COLOR            = 'deep-blue';
    private const SECOND_TRACKER_SHORTNAME        = 'story';

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
        $this->glyph_finder->method('get')->willReturn(new Glyph('<svg>icon</svg>'));
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

        $project = ProjectTestBuilder::aProject()->withId(208)->build();

        $first_tracker  = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withShortName(self::FIRST_TRACKER_SHORTNAME)
            ->withColor(TrackerColor::fromName(self::FIRST_TRACKER_COLOR))
            ->build();
        $first_artifact = ArtifactTestBuilder::anArtifact(self::FIRST_ARTIFACT_ID)
            ->inTracker($first_tracker)
            ->withTitle(self::FIRST_ARTIFACT_TITLE)
            ->build();

        $second_tracker  = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withShortName(self::SECOND_TRACKER_SHORTNAME)
            ->withColor(TrackerColor::fromName(self::SECOND_TRACKER_COLOR))
            ->build();
        $second_artifact = ArtifactTestBuilder::anArtifact(self::SECOND_ARTIFACT_ID)
            ->inTracker($second_tracker)
            ->withTitle(self::SECOND_ARTIFACT_TITLE)
            ->build();

        $this->artifact_factory->method('getArtifactById')->willReturnOnConsecutiveCalls(
            $first_artifact,
            $second_artifact
        );
        $max_length_history = 2;
        $collection         = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection, $max_length_history);

        $this->assertCount($max_length_history, $collection->getEntries());
        foreach ($collection->getEntries() as $entry) {
            self::assertSame(VisitRetriever::TYPE, $entry->getType());
            self::assertNotNull($entry->getSmallIcon());
            self::assertNotNull($entry->getNormalIcon());
            self::assertSame('fa-solid fa-tlp-tracker', $entry->getIconName());
            self::assertSame($project, $entry->getProject());
            self::assertEmpty($entry->getQuickLinks());
            self::assertEmpty($entry->getBadges());
        }

        [$first_entry, $second_entry] = $collection->getEntries();
        $this->assertSame(self::FIRST_ARTIFACT_VISIT_TIMESTAMP, $first_entry->getVisitTime());
        self::assertSame(self::FIRST_ARTIFACT_ID, $first_entry->getPerTypeId());
        self::assertSame(self::FIRST_ARTIFACT_TITLE, $first_entry->getTitle());
        self::assertSame(self::FIRST_TRACKER_COLOR, $first_entry->getColor());
        self::assertSame(sprintf('/plugins/tracker/?aid=%d', self::FIRST_ARTIFACT_ID), $first_entry->getLink());
        self::assertSame(sprintf('%s #%d', self::FIRST_TRACKER_SHORTNAME, self::FIRST_ARTIFACT_ID), $first_entry->getXref());

        $this->assertSame(self::SECOND_ARTIFACT_VISIT_TIMESTAMP, $second_entry->getVisitTime());
        self::assertSame(self::SECOND_ARTIFACT_ID, $second_entry->getPerTypeId());
        self::assertSame(self::SECOND_ARTIFACT_TITLE, $second_entry->getTitle());
        self::assertSame(self::SECOND_TRACKER_COLOR, $second_entry->getColor());
        self::assertSame(sprintf('/plugins/tracker/?aid=%d', self::SECOND_ARTIFACT_ID), $second_entry->getLink());
        self::assertSame(sprintf('%s #%d', self::SECOND_TRACKER_SHORTNAME, self::SECOND_ARTIFACT_ID), $second_entry->getXref());
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
