<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Builders;

use Tracker_FormElement_Field_String;

final class TrackerFormElementStringFieldBuilder
{
    private string $label     = "Title";
    private string $name      = "title";
    private bool $is_required = false;

    private function __construct(private readonly int $id)
    {
    }

    public static function aStringField(int $id): self
    {
        return new self($id);
    }

    public function thatIsRequired(): self
    {
        $this->is_required = true;
        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_String
    {
        return new Tracker_FormElement_Field_String(
            $this->id,
            10,
            15,
            $this->name,
            $this->label,
            "",
            true,
            'P',
            $this->is_required,
            '',
            10,
            null
        );
    }
}
