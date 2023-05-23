<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\Config\FeatureFlagConfigKey;

final class PullRequestV2FeatureFlag
{
    #[FeatureFlagConfigKey("Feature flag to disable pullrequest v2 in some projects. Comma separated list of project ids, 0 or 1.")]
    public const FEATURE_FLAG_KEY = 'disable_pullrequest_v2';

    public static function isPullRequestV2Displayed(\GitRepository $repository): bool
    {
        $feature_flag_value = \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY);
        if ($feature_flag_value === "0" || ! $feature_flag_value) {
            return true;
        }

        if ($feature_flag_value === "1") {
            return false;
        }

        $projects_disabling_pullrequest_v2 = explode(',', $feature_flag_value);
        return ! in_array((string) $repository->getProjectId(), $projects_disabling_pullrequest_v2, true);
    }
}
