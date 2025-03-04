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

use Cardwall_OnTop_Config_Command_EnableCardwallOnTop;
use Cardwall_OnTop_Dao;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_EnableCardwallOnTopTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private int $tracker_id;
    private Cardwall_OnTop_Dao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_EnableCardwallOnTop $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;
        $tracker          = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();
        $this->dao        = $this->createMock(Cardwall_OnTop_Dao::class);
        $this->command    = new Cardwall_OnTop_Config_Command_EnableCardwallOnTop($tracker, $this->dao);
    }

    public function testItEnablesIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('cardwall_on_top', '1');
        $this->dao->method('isEnabled')->with($this->tracker_id)->willReturn(false);
        $this->dao->expects(self::once())->method('enable')->with($this->tracker_id);

        $this->command->execute($request);
    }

    public function testItDoesNotEnableIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('cardwall_on_top', '1');
        $this->dao->method('isEnabled')->with($this->tracker_id)->willReturn(true);
        $this->dao->expects(self::never())->method('enable');

        $this->command->execute($request);
    }

    public function testItDisablesIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('cardwall_on_top', '0');
        $this->dao->method('isEnabled')->with($this->tracker_id)->willReturn(true);
        $this->dao->expects(self::once())->method('disable')->with($this->tracker_id);

        $this->command->execute($request);
    }

    public function testItDoesNotDisableIfItIsNotAlreadyTheCase(): void
    {
        $request = new HTTPRequest();
        $request->set('cardwall_on_top', '0');
        $this->dao->method('isEnabled')->with($this->tracker_id)->willReturn(false);
        $this->dao->expects(self::never())->method('disable');

        $this->command->execute($request);
    }
}
