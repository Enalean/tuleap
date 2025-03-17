<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Builders\Fields\List;

use Tracker_FormElement_Field_List_OpenValue;

final class OpenListStaticValueBuilder
{
    private int $id         = 1;
    private bool $is_hidden = false;

    private function __construct(private readonly string $value)
    {
    }

    public static function aStaticValue(string $value): self
    {
        return new self($value);
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function isHidden(bool $is_hidden): self
    {
        $this->is_hidden = $is_hidden;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_List_OpenValue
    {
        return new Tracker_FormElement_Field_List_OpenValue($this->id, $this->value, $this->is_hidden);
    }
}
