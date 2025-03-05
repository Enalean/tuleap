<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\GraphicLibrary;

use Tuleap\GlobalLanguageMock;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class GraphOnTrackersV5_Engine_BurndownTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testDurationIsValidWhenItsLongerThanOneDay(): void
    {
        $burndown_engine           = new GraphOnTrackersV5_Engine_Burndown();
        $burndown_engine->title    = 'my burndown title';
        $burndown_engine->duration = 2;

        $this->assertTrue($burndown_engine->validData());
    }

    public function testDurationIsInvalid(): void
    {
        $burndown_engine           = new GraphOnTrackersV5_Engine_Burndown();
        $burndown_engine->title    = 'my burndown title';
        $burndown_engine->duration = 1;

        $this->assertFalse($burndown_engine->validData());
        $this->expectOutputString(" <p class='feedback_info'>No datas to display for graph my burndown title</p>");
    }
}
