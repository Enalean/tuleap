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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;

/**
 * I hold the original (unmapped) list value labels of the Status semantic field of the source Timebox.
 * I am the reference value labels that must be mapped to matching bind value identifiers.
 * in the Mirrored Timeboxes' trackers before being saved in Mirrored Timeboxes.
 * @see MappedStatusValue
 * @psalm-immutable
 */
final class StatusValue
{
    /**
     * @var BindValueLabel[]
     */
    private array $labels;

    private function __construct(BindValueLabel ...$labels)
    {
        $this->labels = $labels;
    }

    /**
     * @throws ChangesetValueNotFoundException
     */
    public static function fromStatusReference(
        RetrieveStatusValues $status_retriever,
        StatusFieldReference $status,
    ): self {
        $labels = $status_retriever->getStatusValues($status);
        return new self(...$labels);
    }

    /**
     * @return BindValueLabel[]
     */
    public function getListValues(): array
    {
        return $this->labels;
    }
}
