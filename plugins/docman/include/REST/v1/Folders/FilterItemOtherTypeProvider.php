<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_FilterItemType;
use Luracast\Restler\RestException;
use Tuleap\Event\Dispatchable;

final class FilterItemOtherTypeProvider implements Dispatchable
{
    public function __construct(
        public readonly Docman_FilterItemType $filter,
        public readonly string $name,
    ) {
    }

    public function getExternalFilter(): Docman_FilterItemType
    {
        if ($this->filter->getAlternateValue() !== null) {
            return $this->filter;
        }

        throw new RestException(400, 'Unknown type ' . $this->name);
    }

    public function setValue(string $value): void
    {
        $this->filter->setValue(\Docman_Item::TYPE_OTHER);
        $this->filter->setAlternateValue($value);
    }
}
