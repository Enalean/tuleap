<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Colorpicker;

use Tuleap\Config\FeatureFlagConfigKey;

/**
 * @psalm-immutable
 */
final class ColorpickerMountPointPresenterBuilder
{
    #[FeatureFlagConfigKey('Feature flag to enable usage of legacy color palette. Please warn us if you activate this flag.')]
    public const FEATURE_FLAG = 'enable_usage_of_legacy_color_palette';

    public static function buildPresenter(
        ?string $current_color,
        string $input_name,
        string $input_id,
        bool $is_field_used_in_semantic,
    ): ColorpickerMountPointPresenter {
        $is_switch_disabled     = $is_field_used_in_semantic;
        $is_old_palette_enabled = true;

        if (\ForgeConfig::getFeatureFlag(self::FEATURE_FLAG) !== 'contact_the_dev_team_if_you_enable_this') {
            $is_switch_disabled     = true;
            $is_old_palette_enabled = false;
        }


        return new ColorpickerMountPointPresenter(
            ($current_color) ? $current_color : '',
            $input_name,
            $input_id,
            $is_switch_disabled,
            $is_old_palette_enabled,
        );
    }
}
