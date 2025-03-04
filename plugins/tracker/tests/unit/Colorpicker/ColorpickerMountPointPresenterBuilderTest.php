<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Colorpicker;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ColorpickerMountPointPresenterBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testWhenColorIsNullColorWillBeEmptyString(): void
    {
        self::assertEquals(
            '',
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', true)->current_color
        );
        self::assertEquals(
            'fiesta-red',
            ColorpickerMountPointPresenterBuilder::buildPresenter('fiesta-red', 'name', 'id', true)->current_color
        );
    }

    public function testSwitchToLegacyPaletteIsDisabledIfFieldIsUsedInSemanticAndFeatureFlagIsActivated(): void
    {
        \ForgeConfig::setFeatureFlag('enable_usage_of_legacy_color_palette', 'contact_the_dev_team_if_you_enable_this');
        self::assertFalse(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', false)->is_switch_disabled
        );
        self::assertTrue(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', true)->is_switch_disabled
        );
        self::assertTrue(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', false)->is_old_palette_enabled
        );
        self::assertTrue(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', true)->is_old_palette_enabled
        );
    }

    public function testSwitchToLegacyPaletteIsDisabledIfFeatureFlagIsNotActivated(): void
    {
        self::assertTrue(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', false)->is_switch_disabled
        );
        self::assertTrue(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', true)->is_switch_disabled
        );
        self::assertFalse(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', false)->is_old_palette_enabled
        );
        self::assertFalse(
            ColorpickerMountPointPresenterBuilder::buildPresenter(null, 'name', 'id', true)->is_old_palette_enabled
        );
    }
}
