<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders\Fields\List;

use Tracker_FormElement_Field_List_BindDecorator;
use Tuleap\Color\ColorName;

final class StaticBindDecoratorBuilder
{
    private int $field_id = 100;
    private int $value_id = 1;

    private function __construct(
        private readonly ColorName $tlp_color,
    ) {
    }

    public static function withColor(ColorName $tlp_color): self
    {
        return new self($tlp_color);
    }

    public function withFieldId(int $field_id): self
    {
        $this->field_id = $field_id;
        return $this;
    }

    public function withValueId(int $value_id): self
    {
        $this->value_id = $value_id;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_List_BindDecorator
    {
        return new Tracker_FormElement_Field_List_BindDecorator(
            $this->field_id,
            $this->value_id,
            null,
            null,
            null,
            $this->tlp_color->value,
        );
    }
}
