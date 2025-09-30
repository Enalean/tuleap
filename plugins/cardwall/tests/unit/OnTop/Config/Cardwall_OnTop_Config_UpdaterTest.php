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

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_OnTop_Config_Command;
use Cardwall_OnTop_Config_Updater;
use Codendi_Request;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_UpdaterTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    public function testItScheduleExecuteOnCommands(): void
    {
        $request = new Codendi_Request([]);
        $c1      = $this->createMock(Cardwall_OnTop_Config_Command::class);
        $c2      = $this->createMock(Cardwall_OnTop_Config_Command::class);
        $updater = new Cardwall_OnTop_Config_Updater();
        $updater->addCommand($c1);
        $updater->addCommand($c2);

        $c1->expects($this->once())->method('execute')->with($request);
        $c2->expects($this->once())->method('execute')->with($request);

        $updater->process($request);
    }
}
