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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

require_once __DIR__ . '/../bootstrap.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class GraphOnTrackersV5_Engine_BurndownTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testDurationIsValidWhenItsLongerThanOneDay(): void
    {
        $burndown_engine           = new GraphOnTrackersV5_Engine_Burndown();
        $burndown_engine->title    = "my burndown title";
        $burndown_engine->duration = 2;

        $this->assertTrue($burndown_engine->validData());
    }

    public function testDurationIsInvalid(): void
    {
        $burndown_engine           = new GraphOnTrackersV5_Engine_Burndown();
        $burndown_engine->title    = "my burndown title";
        $burndown_engine->duration = 1;

        $this->assertFalse($burndown_engine->validData());
        $this->expectOutputString(" <p class='feedback_info'></p>");
    }
}
