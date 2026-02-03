<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Admin;

use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;

#[ConfigKeyCategory('Tracker')]
final readonly class FieldsUsageConfiguration
{
    #[FeatureFlagConfigKey(<<<EOT
    Should we display new fields usage interface?
    Comma separated list of project ids like 123,234.
    0 => No projects have the feature (default)
    1 => Every projects have the feature
    123,234 => Only projects with id 123 or 234 have the feature
    EOT)]
    #[ConfigKeyString('0')]
    public const string FEATURE_FLAG = 'enable_new_fields_usage_interface';
}
