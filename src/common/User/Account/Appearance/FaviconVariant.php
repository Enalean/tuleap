<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Appearance;

use ForgeConfig;
use PFUser;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;

#[ConfigKeyCategory('Theme')]
final class FaviconVariant
{
    #[FeatureFlagConfigKey('Allow users to have a favicon according to current theme variant. 0 to disallow, 1 to allow. Default is disallow.')]
    #[ConfigKeyHidden]
    #[ConfigKeyInt(0)]
    public const string FEATURE_FLAG = 'allow_favicon_variant';

    public const string PREFERENCE_NAME      = 'use_favicon_variant';
    public const string PREFERENCE_VALUE_ON  = '1';
    public const string PREFERENCE_VALUE_OFF = '0';

    public static function isFeatureFlagEnabled(): bool
    {
        return (string) ForgeConfig::getFeatureFlag(self::FEATURE_FLAG) === '1';
    }

    public static function shouldDisplayFaviconVariant(PFUser $user): bool
    {
        if (! self::isFeatureFlagEnabled()) {
            return false;
        }

        return $user->getPreference(self::PREFERENCE_NAME) === self::PREFERENCE_VALUE_ON;
    }
}
