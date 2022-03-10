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

use Tuleap\Config\FeatureFlagConfigKey;

final class ListPickerIncluder
{
    #[FeatureFlagConfigKey("Feature flag to have list pickers in lieu of <select> in artifact views")]
    public const FORGE_CONFIG_KEY = 'use_list_pickers_in_trackers_and_modals';

    public static function includeListPickerAssets(int $tracker_id): void
    {
        if (self::isListPickerEnabledAndBrowserCompatible($tracker_id)) {
            $include_assets = new \Tuleap\Layout\IncludeAssets(
                __DIR__ . '/../../../../frontend-assets',
                '/assets/trackers'
            );

            $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('list-fields.js'));
        }
    }

    public static function includeArtifactLinksListPickerAssets(int $tracker_id): void
    {
        if (self::isListPickerEnabledAndBrowserCompatible($tracker_id)) {
            $include_assets = new \Tuleap\Layout\IncludeAssets(
                __DIR__ . '/../../../../frontend-assets',
                '/assets/trackers'
            );

            $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('artifact-links-field.js'));
        }
    }

    public static function isListPickerEnabledAndBrowserCompatible(int $tracker_id): bool
    {
        if (self::isListPickerEnabledOnPlatform() === false) {
            return false;
        }

        if (self::isFeatureDisabledForCurrentTracker($tracker_id)) {
            return false;
        }

        return true;
    }

    public static function isListPickerEnabledOnPlatform(): bool
    {
        return \ForgeConfig::getFeatureFlag(self::FORGE_CONFIG_KEY) !== "0";
    }

    /**
     * @return string[]
     */
    public static function getTrackersHavingListPickerDisabled(): array
    {
        $config_value               = \ForgeConfig::getFeatureFlag(self::FORGE_CONFIG_KEY);
        $tracker_id_prefix_position = strpos($config_value, "t:");
        if ($tracker_id_prefix_position === false) {
            return [];
        }

        return explode(
            ",",
            str_replace(
                "t:",
                "",
                $config_value
            )
        );
    }

    private static function isFeatureDisabledForCurrentTracker(int $tracker_id): bool
    {
        return array_search($tracker_id, self::getTrackersHavingListPickerDisabled()) !== false;
    }
}
