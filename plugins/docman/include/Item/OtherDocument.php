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

namespace Tuleap\Docman\Item;

abstract class OtherDocument extends \Docman_Document
{
    public function __construct(array $row)
    {
        parent::__construct($row);
    }

    #[\Override]
    public function accept(ItemVisitor $visitor, array $params = [])
    {
        return $visitor->visitOtherDocument($this, $params);
    }

    #[\Override]
    public function toRow(): array
    {
        $row              = parent::toRow();
        $row['item_type'] = \Docman_Item::TYPE_OTHER;

        return $row;
    }
}
