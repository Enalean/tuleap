<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\FeatureFlag;

use Tuleap\ForgeConfigSandbox;

final class ForgeConfigAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItReturnsTrueWhenSetToOne(): void
    {
        \ForgeConfig::set('feature_flag_program_management_display_iteration', '1');
        $adapter = new ForgeConfigAdapter();

        self::assertTrue($adapter->isIterationsFeatureActive());
    }

    public function testItReturnsFalseWhenSetToZero(): void
    {
        \ForgeConfig::set('feature_flag_program_management_display_iteration', '0');
        $adapter = new ForgeConfigAdapter();

        self::assertFalse($adapter->isIterationsFeatureActive());
    }

    public function testItReturnsFalseWhenNotSet(): void
    {
        $adapter = new ForgeConfigAdapter();

        self::assertFalse($adapter->isIterationsFeatureActive());
    }
}
