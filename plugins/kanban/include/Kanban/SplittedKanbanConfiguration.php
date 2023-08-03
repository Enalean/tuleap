<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\FeatureFlagConfigKey;

#[ConfigKeyCategory('Kanban')]
final class SplittedKanbanConfiguration
{
    #[FeatureFlagConfigKey('Should we display kanban homepage back in A.D homepage for some projects? Comma separated list of project ids like 123,234. Default to 0 (no projects deactivate splitted kanban) ⚠️  This flag is temporary, please get in touch with Enalean Team if you are using it.')]
    #[ConfigKeyString('0')]
    public const FEATURE_FLAG = 'temporarily_deactivate_splitted_kanban_for_project';
}
