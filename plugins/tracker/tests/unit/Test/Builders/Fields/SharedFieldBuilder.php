<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders\Fields;

use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;

final class SharedFieldBuilder
{
    private Tracker $tracker;
    private string $label     = 'Shared';
    private bool $is_required = false;

    private function __construct(
        private readonly int $id,
        private readonly TrackerField $original_field,
    ) {
    }

    public static function aSharedField(int $id, TrackerField $original_field): self
    {
        return new self($id, $original_field);
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function inTracker(Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function thatIsRequired(): self
    {
        $this->is_required = true;
        return $this;
    }

    public function build(): SelectboxField
    {
        $field = new SelectboxField(
            $this->id,
            $this->tracker->getId(),
            15,
            'shared',
            $this->label,
            '',
            true,
            'P',
            $this->is_required,
            '',
            10,
            $this->original_field,
        );
        $field->setTracker($this->tracker);

        return $field;
    }
}
