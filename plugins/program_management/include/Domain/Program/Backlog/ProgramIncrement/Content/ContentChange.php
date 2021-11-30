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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\FeaturesToReorder;

/**
 * @psalm-immutable
 */
final class ContentChange
{
    private function __construct(
        public ?int $potential_feature_id_to_add,
        public ?FeaturesToReorder $elements_to_order,
    ) {
    }

    /**
     * @throws AddOrOrderMustBeSetException
     */
    public static function fromFeatureAdditionAndReorder(
        ?int $potential_feature_id_to_add,
        ?FeaturesToReorder $elements_to_order,
    ): self {
        if ($potential_feature_id_to_add === null && $elements_to_order === null) {
            throw new AddOrOrderMustBeSetException();
        }
        return new self($potential_feature_id_to_add, $elements_to_order);
    }
}
