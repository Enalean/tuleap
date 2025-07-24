<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;

final class RetrieveIterationLabelsStub implements RetrieveIterationLabels
{
    private ?string $label;
    private ?string $sub_label;

    private function __construct(?string $label, ?string $sub_label)
    {
        $this->label     = $label;
        $this->sub_label = $sub_label;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getIterationLabels(int $iteration_tracker_id): ?array
    {
        if ($this->label === null && $this->sub_label === null) {
            return null;
        }
        return ['iteration_label' => $this->label, 'iteration_sub_label' => $this->sub_label];
    }

    public static function buildLabels(?string $label, ?string $sub_label): self
    {
        return new self($label, $sub_label);
    }
}
