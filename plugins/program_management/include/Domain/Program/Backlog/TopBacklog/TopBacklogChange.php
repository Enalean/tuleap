<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog;

/**
 * @psalm-immutable
 */
final class TopBacklogChange
{
    /**
     * @param int[] $potential_features_id_to_add
     * @param int[] $potential_features_id_to_remove
     */
    public function __construct(
        public array $potential_features_id_to_add,
        public array $potential_features_id_to_remove,
        public bool $remove_program_increments_link_to_feature_to_add,
        public ?FeaturesToReorder $elements_to_order,
    ) {
    }
}
