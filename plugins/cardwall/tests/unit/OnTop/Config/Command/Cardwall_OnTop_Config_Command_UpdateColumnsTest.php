<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config\Command;

use Cardwall_OnTop_ColumnDao;
use Cardwall_OnTop_Config_Command_UpdateColumns;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_UpdateColumnsTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $tracker_id;
    private Cardwall_OnTop_ColumnDao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_UpdateColumns $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_id = 666;

        $tracker = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->dao     = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_UpdateColumns($tracker, $this->dao);
    }

    public function testItUpdatesAllColumns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            [
                12 => ['label' => 'Todo', 'bgcolor' => '#000000'],
                13 => ['label' => ''],
                14 => ['label' => 'Done', 'bgcolor' => '#16ed9d'],
            ]
        );
        $matcher = self::exactly(2);
        $this->dao->expects($matcher)
            ->method('save')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(12, $parameters[1]);
                    self::assertSame('Todo', $parameters[2]);
                    self::assertSame(0, $parameters[3]);
                    self::assertSame(0, $parameters[4]);
                    self::assertSame(0, $parameters[5]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(14, $parameters[1]);
                    self::assertSame('Done', $parameters[2]);
                    self::assertSame(22, $parameters[3]);
                    self::assertSame(237, $parameters[4]);
                    self::assertSame(157, $parameters[5]);
                }
            });
        $this->command->execute($request);
    }
}
