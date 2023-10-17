<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Tuleap\Config\FeatureFlagConfigKey;

final class SwitchToOldUi
{
    #[FeatureFlagConfigKey(<<<'EOF'
    Feature flag to allow using the old docman interface. Comma-separated list of project ids.
    ⚠️  Please warn us if you activate this flag.
    EOF
    )]
    public const FEATURE_FLAG = 'allow_temporary_access_to_old_ui_that_will_be_removed_soon';

    public static function isAllowed(\PFUser $user, \Project $project): bool
    {
        if ($user->isAnonymous()) {
            return false;
        }

        $comma_separated_project_ids = \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG);
        if (! $comma_separated_project_ids) {
            return false;
        }

        $allowed_project_ids = explode(',', $comma_separated_project_ids);

        return in_array((string) $project->getID(), $allowed_project_ids, true);
    }
}
