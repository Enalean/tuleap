<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementArtifactLinkType;

/**
 * @psalm-immutable
 */
final class ArtifactLinkValue
{
    /**
     * @var int
     */
    private $source_artifact_id;

    public function __construct(int $source_artifact_id)
    {
        $this->source_artifact_id = $source_artifact_id;
    }

    /**
     * @return array{new_values: string, natures: array<string, string>}
     */
    public function getValues(): array
    {
        return [
            'new_values' => (string) $this->source_artifact_id,
            'natures'    => [(string) $this->source_artifact_id => ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME]
        ];
    }
}
