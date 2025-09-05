<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\FeaturesToReorder;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;

/**
 * @psalm-immutable
 */
final class FeaturesToReorderProxy implements FeaturesToReorder
{
    /**
     * @param int[] $ids
     */
    private function __construct(private array $ids, private string $direction, private int $compared_to)
    {
    }

    public static function buildFromRESTRepresentation(?FeatureElementToOrderInvolvedInChangeRepresentation $change_representation): ?self
    {
        if (! $change_representation) {
            return null;
        }

        return new self(
            $change_representation->ids,
            $change_representation->direction,
            $change_representation->compared_to,
        );
    }

    /**
     * @return int[]
     */
    #[\Override]
    public function getIds(): array
    {
        return $this->ids;
    }

    #[\Override]
    public function getDirection(): string
    {
        return $this->direction;
    }

    #[\Override]
    public function getComparedTo(): int
    {
        return $this->compared_to;
    }

    #[\Override]
    public function isBefore(): bool
    {
        return $this->direction === FeatureElementToOrderInvolvedInChangeRepresentation::BEFORE;
    }
}
