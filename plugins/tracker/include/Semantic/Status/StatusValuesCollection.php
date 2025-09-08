<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindValueIdCollection;

final class StatusValuesCollection implements BindValueIdCollection
{
    /**
     * @param int[] $value_ids
     */
    public function __construct(private array $value_ids)
    {
    }

    #[\Override]
    public function getValueIds(): array
    {
        return $this->value_ids;
    }

    #[\Override]
    public function removeValue(int $value): void
    {
        $key = array_search($value, $this->value_ids);

        if ($key !== false) {
            unset($this->value_ids[$key]);
        }
    }

    #[\Override]
    public function getFirstValue(): int
    {
        return reset($this->value_ids);
    }
}
