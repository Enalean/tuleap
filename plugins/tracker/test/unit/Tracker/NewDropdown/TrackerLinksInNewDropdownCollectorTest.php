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
use PHPUnit\Framework\TestCase;
use Tuleap\layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\layout\NewDropdown\NewDropdownProjectLinksCollector;

class TrackerLinksInNewDropdownCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCollectsLinksForTrackers(): void
    {
        $bug_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getItemName' => 'bug',
                'getSubmitUrl' => '/path/to/submit/bugs'
            ])
            ->getMock();
        $story_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getItemName' => 'story',
                'getSubmitUrl' => '/path/to/submit/story'
            ])
            ->getMock();

        $retriever = Mockery::mock(TrackerInNewDropdownRetriever::class);
        $retriever
            ->shouldReceive('getTrackers')
            ->andReturn([$bug_tracker, $story_tracker]);

        $links_collector = Mockery::spy(NewDropdownProjectLinksCollector::class);
        $links_collector
            ->shouldReceive('addCurrentProjectLink')
            ->twice();

        $collector = new TrackerLinksInNewDropdownCollector($retriever);
        $collector->collect($links_collector);
    }

    public function testItOmitsTrackersThatAreAlreadyInTheCurrentContextSection(): void
    {
        $bug_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getItemName' => 'bug',
                'getSubmitUrl' => '/path/to/submit/bugs'
            ])
            ->getMock();
        $story_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getItemName' => 'story',
                'getSubmitUrl' => '/path/to/submit/story'
            ])
            ->getMock();

        $retriever = Mockery::mock(TrackerInNewDropdownRetriever::class);
        $retriever
            ->shouldReceive('getTrackers')
            ->andReturn([$bug_tracker, $story_tracker]);

        $links_collector = Mockery::spy(NewDropdownProjectLinksCollector::class);
        $links_collector
            ->shouldReceive('addCurrentProjectLink')
            ->once();

        $current_context_section = new NewDropdownLinkSectionPresenter("section label", [
            new \Tuleap\layout\NewDropdown\NewDropdownLinkPresenter('/path/to/submit/story', 'New story', 'fa-plus')
        ]);

        $links_collector
            ->shouldReceive('getCurrentContextSection')
            ->once()
            ->andReturn($current_context_section);

        $collector = new TrackerLinksInNewDropdownCollector($retriever);
        $collector->collect($links_collector);
    }
}
