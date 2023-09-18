<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Home;

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

final class KanbanHomePresenterTest extends TestCase
{
    /**
     *
     * @psalm-param list<array{id: int, name: string, used: bool}> $trackers
     *
     * @dataProvider dataProviderTrackersWithKanbanUsage
     */
    public function testAreTrackersAvailable(array $trackers, bool $expected): void
    {
        $presenter = new KanbanHomePresenter(
            [],
            false,
            $trackers,
            '',
            CSRFSynchronizerTokenPresenter::fromToken(CSRFSynchronizerTokenStub::buildSelf())
        );

        self::assertEquals($expected, $presenter->are_trackers_available);
    }

    public static function dataProviderTrackersWithKanbanUsage(): array
    {
        return [
            'no trackers' => [[], false],
            'a tracker used' => [[self::aTrackerPresenter(true)], false],
            'a tracker not used' => [[self::aTrackerPresenter(false)], true],
            'all trackers used' => [[self::aTrackerPresenter(true), self::aTrackerPresenter(true)], false],
            'at least one tracker unused' => [[self::aTrackerPresenter(true), self::aTrackerPresenter(false)], true],
        ];
    }

    /**
     * @psalm-return array{id: int, name: string, used: bool}
     */
    private static function aTrackerPresenter(bool $used): array
    {
        return [
            'id'   => 1,
            'name' => 'name',
            'used' => $used,
        ];
    }
}
