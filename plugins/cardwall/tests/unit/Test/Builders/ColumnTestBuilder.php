<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\Test\Builders;

use Tuleap\Cardwall\OnTop\Config\ColumnFactory;

final class ColumnTestBuilder
{
    private int $id       = 45;
    private string $label = 'Todo';

    private function __construct()
    {
    }

    public static function aColumn(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function build(): \Cardwall_Column
    {
        return new \Cardwall_Column(
            $this->id,
            $this->label,
            ColumnFactory::DEFAULT_HEADER_COLOR,
        );
    }
}
