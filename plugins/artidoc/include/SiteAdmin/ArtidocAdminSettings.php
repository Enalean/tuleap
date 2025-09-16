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

namespace Tuleap\Artidoc\SiteAdmin;

use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;

#[ConfigKeyCategory('Artidoc')]
final readonly class ArtidocAdminSettings
{
    #[FeatureFlagConfigKey('Allow users to display artidoc versions. 0 to disallow, 1 to allow. By default they are disallowed.')]
    #[ConfigKeyInt(0)]
    public const string FEATURE_FLAG_VERSIONS = 'can_user_display_versions';

    private function __construct()
    {
    }
}
