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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;

final class ArtifactLinkValueFormatter
{
    /**
     * @return array{new_values: string, types: array<string, string>}
     */
    public function formatForTrackerPlugin(?ArtifactLinkValue $value): array
    {
        if (! $value) {
            return [
                'new_values' => '',
                'types'      => []
            ];
        }
        return [
            'new_values' => (string) $value->linked_artifact->getId(),
            'types'      => [(string) $value->linked_artifact->getId() => (string) $value->type]
        ];
    }
}
