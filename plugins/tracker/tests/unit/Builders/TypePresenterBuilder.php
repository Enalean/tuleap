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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

final class TypePresenterBuilder
{
    private string $shortname     = "type";
    private string $forward_label = "forward_type";
    private string $reverse_label = "reverse_type";

    private function __construct(private readonly bool $is_system)
    {
    }

    public static function aSystemType(): self
    {
        return new self(true);
    }

    public static function aCustomType(): self
    {
        return new self(false);
    }

    public function withShortname(string $shortname): self
    {
        $this->shortname     = $shortname;
        $this->forward_label = 'forward_' . $this->shortname;
        $this->reverse_label = 'reverse_' . $this->shortname;

        return $this;
    }

    public function build(): TypePresenter
    {
        $type = new TypePresenter(
            $this->shortname,
            $this->forward_label,
            $this->reverse_label,
            true
        );

        if ($this->is_system) {
            $type->is_system = true;
        }

        return $type;
    }
}
