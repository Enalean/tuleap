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

use Docman_PermissionsManager;
use PFUser;
use Project;
use Tuleap\Docman\REST\v1\ItemRepresentationBuilder;

final class DocmanServiceRepresentationBuilder
{
    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;
    /**
     * @var Docman_PermissionsManager
     */
    private $docman_permissions_manager;
    /**
     * @var DocmanServicePermissionsForGroupsBuilder
     */
    private $service_permissions_for_groups_builder;

    public function __construct(
        ItemRepresentationBuilder $item_representation_builder,
        Docman_PermissionsManager $docman_permissions_manager,
        DocmanServicePermissionsForGroupsBuilder $service_permissions_for_groups_builder
    ) {
        $this->item_representation_builder            = $item_representation_builder;
        $this->docman_permissions_manager             = $docman_permissions_manager;
        $this->service_permissions_for_groups_builder = $service_permissions_for_groups_builder;
    }

    public function getServiceRepresentation(Project $project, PFUser $user): ?DocmanServiceRepresentation
    {
        if (! $project->usesService(\DocmanPlugin::SERVICE_SHORTNAME)) {
            return null;
        }

        $root_item_representation = $this->item_representation_builder->buildRootId($project, $user);
        if ($root_item_representation === null) {
            return DocmanServiceRepresentation::buildWithNoInformation();
        }

        if (! $this->docman_permissions_manager->userCanAdmin($user)) {
            return DocmanServiceRepresentation::buildWithRootItem($root_item_representation);
        }

        return DocmanServiceRepresentation::buildWithRootItemAndPermissions(
            $this->service_permissions_for_groups_builder->getServicePermissionsForGroupRepresentation($project),
            $root_item_representation
        );
    }
}
