<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Reference;

use Docman_ItemFactory;
use Docman_PermissionsManager;

class DocumentFromReferenceValueFinder
{
    public function findItem(\Project $project, \PFUser $user, string $reference_value): ?\Docman_Item
    {
        $item_factory = $this->getItemFactory((int) $project->getID());

        $item = $item_factory->getItemFromDb((int) $reference_value);
        if (! $item) {
            return null;
        }

        $permissions_manager = Docman_PermissionsManager::instance((int) $project->getID());
        if (! $permissions_manager->userCanAccess($user, $item->getId())) {
            return null;
        }

        return $item;
    }

    private function getItemFactory(int $project_id): Docman_ItemFactory
    {
        return new Docman_ItemFactory($project_id);
    }
}
