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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;

/**
 * I hold the new value of the Artifact link field for Mirrored Timeboxes.
 * I can contain a link to the source Timebox with the _mirrored_milestone type, or no value.
 * I format those values to the array expected by the Tracker field's validation.
 * @psalm-immutable
 */
final class ArtifactLinkValue
{
    private function __construct(private bool $is_empty, private int $source_artifact_id)
    {
    }

    public static function fromSourceTimeboxValues(SourceTimeboxChangesetValues $values): self
    {
        return new self(false, $values->getSourceArtifactId());
    }

    public static function buildEmptyValue(): self
    {
        return new self(true, 0);
    }

    /**
     * @return array{new_values: string, natures: array<string, string>}
     */
    public function getValues(): array
    {
        if ($this->is_empty) {
            return [
                'new_values' => '',
                'natures'    => []
            ];
        }
        return [
            'new_values' => (string) $this->source_artifact_id,
            'natures'    => [(string) $this->source_artifact_id => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME]
        ];
    }
}
