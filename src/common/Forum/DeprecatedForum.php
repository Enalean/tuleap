<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Forum;

use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Layout\BaseLayout;

/**
 * @psalm-immutable
 */
final class DeprecatedForum
{
    #[FeatureFlagConfigKey('Give back temporary access to Forum and News services for a subset of projects')]
    #[ConfigKeyHelp(<<<EOT
    List of projects where News or Forum service should be restored temporarily
    until they are migrated. It will be completely removed in March 2025.
    EOT)]
    #[ConfigKeyHidden]
    #[ConfigKeyString('')]
    public const FEATURE_FLAG_FORUM_EOL = 'deprecated_forum_unblock_list';

    public static function getDeprecationMessage(): string
    {
        return _('Forums and News services are deprecated. They will be completely removed in March 2025.');
    }

    public static function redirectIfNotAllowed(\Project $project, BaseLayout $response): void
    {
        $response->addFeedback(\Feedback::ERROR, self::getDeprecationMessage());
        if (! self::isProjectAllowed($project)) {
            $response->redirect($project->getUrl());
        }
    }

    public static function isProjectAllowed(\Project $project): bool
    {
        return array_search((int) $project->getID(), \ForgeConfig::getFeatureFlagArrayOfInt(self::FEATURE_FLAG_FORUM_EOL), true) !== false;
    }
}
