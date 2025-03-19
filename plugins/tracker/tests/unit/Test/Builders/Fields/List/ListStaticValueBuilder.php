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

use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\DB\DatabaseUUIDV7Factory;

final class ListStaticValueBuilder
{
    private int|string $id      = 1;
    private bool $is_hidden     = false;
    private string $description = '';

    private function __construct(private readonly string $value)
    {
    }

    public static function aStaticValue(string $value): self
    {
        return new self($value);
    }

    public static function noneStaticValue(): self
    {
        return (new self('None'))->withId(\Tracker_FormElement_Field_List::NONE_VALUE);
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withXMLId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function isHidden(bool $is_hidden): self
    {
        $this->is_hidden = $is_hidden;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        return new Tracker_FormElement_Field_List_Bind_StaticValue($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $this->id, $this->value, $this->description, 1, $this->is_hidden);
    }
}
