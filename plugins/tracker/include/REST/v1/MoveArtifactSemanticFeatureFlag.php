<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tuleap\Config\FeatureFlagConfigKey;

/**
 * @psalm-immutable
 */
final class MoveArtifactSemanticFeatureFlag
{
    #[FeatureFlagConfigKey("Feature flag to rollback the move feature to perform a move based on semantic.")]
    public const FEATURE_FLAG_KEY = 'rollback_to_semantic_move_artifact';

    public static function isEnabled(): bool
    {
        return \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY) === "1";
    }
}
