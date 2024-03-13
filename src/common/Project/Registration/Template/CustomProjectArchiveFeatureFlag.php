<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Queue\IsAsyncTaskProcessingAvailable;

final readonly class CustomProjectArchiveFeatureFlag implements VerifyProjectCreationFromArchiveIsAllowed
{
    #[FeatureFlagConfigKey("Feature flag to allow the user to create a new project from a custom zip archive which contains the project XML template")]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG_KEY = 'create_from_custom_archive';

    public function __construct(private IsAsyncTaskProcessingAvailable $worker_availability)
    {
    }

    public function canCreateFromCustomArchive(): bool
    {
        return \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY) === '1' && $this->worker_availability->canProcessAsyncTasks();
    }
}
