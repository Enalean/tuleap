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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Layout\NewDropdown\DataAttributePresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Layout\NewDropdown\NewDropdownProjectLinksCollector;
use Tuleap\Tracker\Test\Stub\RetrievePromotedTrackersStub;

final class TrackerLinksInNewDropdownCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCollectsLinksForTrackers(): void
    {
        $bug_tracker   = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getId' => 102,
                'getItemName' => 'bug',
                'getSubmitUrl' => '/path/to/submit/bugs',
            ])
            ->getMock();
        $story_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getId' => 103,
                'getItemName' => 'story',
                'getSubmitUrl' => '/path/to/submit/story',
            ])
            ->getMock();

        $retriever = RetrievePromotedTrackersStub::withTrackers($bug_tracker, $story_tracker);

        $links_collector = Mockery::spy(NewDropdownProjectLinksCollector::class);
        $links_collector
            ->shouldReceive('addCurrentProjectLink')
            ->twice();

        $collector = new TrackerLinksInNewDropdownCollector($retriever, new TrackerNewDropdownLinkPresenterBuilder());
        $collector->collect($links_collector);
    }

    public function testItOmitsTrackersThatAreAlreadyInTheCurrentContextSection(): void
    {
        $bug_tracker   = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getId' => 102,
                'getItemName' => 'bug',
                'getSubmitUrl' => '/path/to/submit/bugs',
            ])
            ->getMock();
        $story_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getId' => 103,
                'getItemName' => 'story',
                'getSubmitUrl' => '/path/to/submit/story',
            ])
            ->getMock();

        $retriever = RetrievePromotedTrackersStub::withTrackers($bug_tracker, $story_tracker);

        $links_collector = Mockery::spy(NewDropdownProjectLinksCollector::class);
        $links_collector
            ->shouldReceive('addCurrentProjectLink')
            ->once();

        $current_context_section = new NewDropdownLinkSectionPresenter("section label", [
            new \Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter(
                '/path/to/submit/story',
                'New story',
                'fa-plus',
                [new DataAttributePresenter('tracker-id', '103')],
            ),
        ]);

        $links_collector
            ->shouldReceive('getCurrentContextSection')
            ->once()
            ->andReturn($current_context_section);

        $collector = new TrackerLinksInNewDropdownCollector($retriever, new TrackerNewDropdownLinkPresenterBuilder());
        $collector->collect($links_collector);
    }
}
