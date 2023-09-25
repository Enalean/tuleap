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

namespace Tuleap\Tracker\Admin\GlobalAdmin\Trackers;

use CSRFSynchronizerToken;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;

final class TrackersDisplayPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItSortsAlphabeticallyTheTrackers(): void
    {
        $story = new TrackerPresenter(1001, "story", "User Story", "", false, "/path/to/story", '/path/to/del', true, "");
        $bug   = new TrackerPresenter(1002, "bug", "Bugs", "", false, "/path/to/bug", '/path/to/del', true, "");

        $presenter = new TrackersDisplayPresenter(
            Mockery::mock(Project::class)->shouldReceive(['getID' => 123, 'getUnixNameLowerCase' => 'acme-project'])->getMock(),
            [$story, $bug],
            Mockery::mock(CSRFSynchronizerToken::class),
            true,
        );

        self::assertEquals(
            [$bug, $story],
            $presenter->trackers,
        );
    }
}
