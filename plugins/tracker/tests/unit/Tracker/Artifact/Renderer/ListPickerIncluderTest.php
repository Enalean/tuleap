<?php
/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Renderer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;

final class ListPickerIncluderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testItIncludesListPickerForModernBrowsers(): void
    {
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "1");

        $will_include_assets = ListPickerIncluder::isListPickerEnabledAndBrowserCompatible(42);

        $this->assertEquals(true, $will_include_assets);
    }

    public function testItDoesNotIncludeListPickerWhenFeatureFlagIsDisabled(): void
    {
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "0");

        $will_include_assets = ListPickerIncluder::isListPickerEnabledAndBrowserCompatible(42);

        $this->assertEquals(false, $will_include_assets);
    }

    public function testItDoesNotIncludeListPickerWhenFeatureIsDisabledForCurrentTracker(): void
    {
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "t:1,2,3");

        $will_include_assets = ListPickerIncluder::isListPickerEnabledAndBrowserCompatible(1);

        $this->assertEquals(false, $will_include_assets);
    }

    public function testItIncludesListPickerWhenTrackerIdIsNotInTheList(): void
    {
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "t:1,2,3");

        $will_include_assets = ListPickerIncluder::isListPickerEnabledAndBrowserCompatible(42);

        $this->assertEquals(true, $will_include_assets);
    }

    public function testItReturnsTrueWhenListPickerIsEnabledOnPlatform(): void
    {
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "1");
        $this->assertEquals(true, ListPickerIncluder::isListPickerEnabledOnPlatform());

        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "t:1,2,3");
        $this->assertEquals(true, ListPickerIncluder::isListPickerEnabledOnPlatform());
    }

    public function testItReturnsFalseWhenListPickerIsDisabled(): void
    {
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . ListPickerIncluder::FORGE_CONFIG_KEY, "0");
        $this->assertEquals(false, ListPickerIncluder::isListPickerEnabledOnPlatform());
    }
}
