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

use Cardwall_OnTop_Config_Command_EnableFreestyleColumns;
use Cardwall_OnTop_Dao;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_EnableFreestyleColumnsTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $tracker_id;
    private Cardwall_OnTop_Dao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_EnableFreestyleColumns $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;
        $tracker          = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->dao     = $this->createMock(Cardwall_OnTop_Dao::class);
        $this->command = new Cardwall_OnTop_Config_Command_EnableFreestyleColumns($tracker, $this->dao);
    }

    public function testItEnablesIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '1');
        $this->dao->method('isFreestyleEnabled')->with($this->tracker_id)->willReturn(false);
        $this->dao->expects($this->once())->method('enableFreestyleColumns')->willReturn($this->tracker_id);

        $this->command->execute($request);
    }

    public function testItDoesNotEnableIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '1');
        $this->dao->method('isFreestyleEnabled')->with($this->tracker_id)->willReturn(true);
        $this->dao->expects(self::never())->method('enableFreestyleColumns');

        $this->command->execute($request);
    }

    public function testItDisablesIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '0');
        $this->dao->method('isFreestyleEnabled')->with($this->tracker_id)->willReturn(true);
        $this->dao->expects($this->once())->method('disableFreestyleColumns')->with($this->tracker_id);

        $this->command->execute($request);
    }

    public function testItDoesNotDisableIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('use_freestyle_columns', '0');
        $this->dao->method('isFreestyleEnabled')->with($this->tracker_id)->willReturn(false);
        $this->dao->expects(self::never())->method('disableFreestyleColumns');

        $this->command->execute($request);
    }
}
