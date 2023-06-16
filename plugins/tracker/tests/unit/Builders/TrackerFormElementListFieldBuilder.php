<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_Selectbox;

final class TrackerFormElementListFieldBuilder
{
    private string $label                              = "A list field";
    private string $name                               = "list";
    private bool $is_required                          = false;
    private bool $is_multiple                          = false;
    private ?Tracker_FormElement_Field_List_Bind $bind = null;

    private function __construct(private readonly int $id)
    {
    }

    public static function aListField(int $id): self
    {
        return new self($id);
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

    public function withBind(?Tracker_FormElement_Field_List_Bind $bind): self
    {
        $this->bind = $bind;
        return $this;
    }

    public function withMultipleField(bool $is_multiple = true): self
    {
        $this->is_multiple = $is_multiple;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_Selectbox
    {
        $selectbox = $this->is_multiple
            ? $this->buildSelectBox(Tracker_FormElement_Field_MultiSelectbox::class)
            : $this->buildSelectBox(Tracker_FormElement_Field_Selectbox::class);

        $selectbox->setBind($this->bind);

        return $selectbox;
    }

    private function buildSelectBox(string $selectbox_class): Tracker_FormElement_Field_Selectbox
    {
        return new $selectbox_class(
            $this->id,
            10,
            15,
            $this->name,
            $this->label,
            "",
            true,
            "",
            $this->is_required,
            false,
            10,
            null
        );
    }
}
