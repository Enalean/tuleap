<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Service;

use Tuleap\Docman\REST\v1\ItemRepresentation;

final class DocmanServiceRepresentation
{
    /**
     * @var DocmanServicePermissionsForGroupsRepresentation {@required false} {@type \Tuleap\Docman\REST\v1\Service\DocmanServicePermissionsForGroupsRepresentation}
     * @psalm-var DocmanServicePermissionsForGroupsRepresentation|null
     */
    public $permissions_for_groups;
    /**
     * @var ItemRepresentation {@required false} {@type \Tuleap\Docman\REST\v1\ItemRepresentation}
     * @psalm-var ItemRepresentation|null
     */
    public $root_item;

    private function __construct(
        ?DocmanServicePermissionsForGroupsRepresentation $permissions_for_groups,
        ?ItemRepresentation $root_item
    ) {
        $this->permissions_for_groups = $permissions_for_groups;
        $this->root_item              = $root_item;
    }

    public static function buildWithNoInformation(): self
    {
        return new self(null, null);
    }

    public static function buildWithRootItem(ItemRepresentation $root_item): self
    {
        return new self(null, $root_item);
    }

    public static function buildWithRootItemAndPermissions(
        DocmanServicePermissionsForGroupsRepresentation $permissions_for_groups,
        ItemRepresentation $root_item
    ): self {
        return new self($permissions_for_groups, $root_item);
    }
}
