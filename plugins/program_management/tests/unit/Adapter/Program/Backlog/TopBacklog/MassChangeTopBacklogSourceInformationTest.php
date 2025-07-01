<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\Color\ItemColor;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Masschange\TrackerMasschangeProcessExternalActionsEvent;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MassChangeTopBacklogSourceInformationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsSourceInformationFromEvent(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = self::buildTracker();
        $event   = new TrackerMasschangeProcessExternalActionsEvent(
            $user,
            $tracker,
            new \Codendi_Request(['masschange-action-program-management-top-backlog' => 'add']),
            [888, 999]
        );

        $expected = new MassChangeTopBacklogSourceInformation(102, [888, 999], $user, 'add');

        self::assertEquals($expected, MassChangeTopBacklogSourceInformation::fromProcessExternalActionEvent($event));
    }

    public function testBuildsSourceInformationFromEventEvenWhenThereIsNoMassChangeActionForTheTopBacklog(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = self::buildTracker();
        $event   = new TrackerMasschangeProcessExternalActionsEvent(
            $user,
            $tracker,
            new \Codendi_Request([]),
            [888, 999]
        );

        $expected = new MassChangeTopBacklogSourceInformation(102, [888, 999], $user, null);

        self::assertEquals($expected, MassChangeTopBacklogSourceInformation::fromProcessExternalActionEvent($event));
    }

    private static function buildTracker(): \Tuleap\Tracker\Tracker
    {
        return new \Tuleap\Tracker\Tracker(
            '140',
            '102',
            'Test',
            'Test tracker',
            'test',
            true,
            '',
            '',
            'A',
            null,
            true,
            false,
            0,
            ItemColor::default(),
            false
        );
    }
}
