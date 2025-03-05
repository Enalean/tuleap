<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\NewDropdown;

use Tuleap\Layout\NewDropdown\DataAttributePresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Layout\NewDropdown\NewDropdownProjectLinksCollector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrievePromotedTrackersStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerLinksInNewDropdownCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCollectsLinksForTrackers(): void
    {
        $bug_tracker   = TrackerTestBuilder::aTracker()->withId(102)->build();
        $story_tracker = TrackerTestBuilder::aTracker()->withId(103)->build();

        $retriever = RetrievePromotedTrackersStub::withTrackers($bug_tracker, $story_tracker);

        $links_collector = new NewDropdownProjectLinksCollector(
            UserTestBuilder::buildWithDefaults(),
            ProjectTestBuilder::aProject()->build(),
            null,
        );

        self::assertSame([], $links_collector->getCurrentProjectLinks());

        $collector = new TrackerLinksInNewDropdownCollector($retriever, new TrackerNewDropdownLinkPresenterBuilder());
        $collector->collect($links_collector);

        $links = $links_collector->getCurrentProjectLinks();
        self::assertCount(2, $links);
        self::assertSame('/plugins/tracker/?tracker=102&func=new-artifact', $links[0]->url);
        self::assertSame('/plugins/tracker/?tracker=103&func=new-artifact', $links[1]->url);
    }

    public function testItOmitsTrackersThatAreAlreadyInTheCurrentContextSection(): void
    {
        $bug_tracker   = TrackerTestBuilder::aTracker()->withId(102)->build();
        $story_tracker = TrackerTestBuilder::aTracker()->withId(103)->build();

        $retriever = RetrievePromotedTrackersStub::withTrackers($bug_tracker, $story_tracker);

        $links_collector = new NewDropdownProjectLinksCollector(
            UserTestBuilder::buildWithDefaults(),
            ProjectTestBuilder::aProject()->build(),
            new NewDropdownLinkSectionPresenter('section label', [
                new \Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter(
                    '/path/to/submit/story',
                    'New story',
                    'fa-plus',
                    [new DataAttributePresenter('tracker-id', '103')],
                ),
            ]),
        );

        self::assertSame([], $links_collector->getCurrentProjectLinks());

        $collector = new TrackerLinksInNewDropdownCollector($retriever, new TrackerNewDropdownLinkPresenterBuilder());
        $collector->collect($links_collector);

        $links = $links_collector->getCurrentProjectLinks();
        self::assertCount(1, $links);
        self::assertSame('/plugins/tracker/?tracker=102&func=new-artifact', $links[0]->url);
    }
}
