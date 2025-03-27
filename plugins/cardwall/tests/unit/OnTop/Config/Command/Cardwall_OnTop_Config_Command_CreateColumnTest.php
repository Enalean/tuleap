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
use Cardwall_OnTop_Config_Command_CreateColumn;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_CreateColumnTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private int $tracker_id;
    private Cardwall_OnTop_ColumnDao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_CreateColumn $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;
        $tracker          = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->dao     = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_CreateColumn($tracker, $this->dao);
    }

    public function testItCreatesANewColumn(): void
    {
        $request = new HTTPRequest();
        $request->set('new_column', 'On Going');
        $this->dao->expects($this->once())->method('create')->with($this->tracker_id, 'On Going');
        $this->command->execute($request);
    }
}
