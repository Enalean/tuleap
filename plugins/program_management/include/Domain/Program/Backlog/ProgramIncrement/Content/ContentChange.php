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

use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;

/**
 * @psalm-immutable
 */
final class ContentChange
{
    /**
     * @var ?int
     */
    public $potential_feature_id_to_add;
    /**
     * @var FeatureElementToOrderInvolvedInChangeRepresentation|null
     */
    public $elements_to_order;

    private function __construct(
        ?int $potential_feature_id_to_add,
        ?FeatureElementToOrderInvolvedInChangeRepresentation $elements_to_order
    ) {
        $this->potential_feature_id_to_add = $potential_feature_id_to_add;
        $this->elements_to_order           = $elements_to_order;
    }

    /**
     * @throws AddOrOrderMustBeSetException
     */
    public static function fromRESTRepresentation(
        ?int $potential_feature_id_to_add,
        ?FeatureElementToOrderInvolvedInChangeRepresentation $elements_to_order
    ): self {
        if ($potential_feature_id_to_add === null && $elements_to_order === null) {
            throw new AddOrOrderMustBeSetException();
        }
        return new self($potential_feature_id_to_add, $elements_to_order);
    }
}
