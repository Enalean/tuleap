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

use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Selectbox;

final class SharedFieldBuilder
{
    private Tracker $tracker;
    private string $label     = 'Shared';
    private bool $is_required = false;

    private function __construct(
        private readonly int $id,
        private readonly Tracker_FormElement_Field $original_field,
    ) {
    }

    public static function aSharedField(int $id, Tracker_FormElement_Field $original_field): self
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

    public function build(): Tracker_FormElement_Field_Selectbox
    {
        $field = new Tracker_FormElement_Field_Selectbox(
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
