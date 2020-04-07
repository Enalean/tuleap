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

use Docman_ItemFactory;
use Docman_VersionFactory;
use Luracast\Restler\RestException;
use PermissionsManager;
use ProjectManager;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\REST\v1\ItemRepresentationBuilder;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use UGroupManager;
use UserHelper;

final class DocmanServiceResource extends AuthenticatedResource
{
    public const RESOURCE_TYPE = 'docman_service';

    /**
     * Get document manager service
     *
     * Gte information about the document manager for a given project
     *
     * @url    GET {id}/docman_service
     * @access hybrid
     *
     * @param int $id Id of the project
     *
     * @throws RestException 404
     */
    public function getService(int $id): DocmanServiceRepresentation
    {
        $this->checkAccess();
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($id);
        if ($project === null) {
            throw new RestException(404);
        }

        $user_manager        = \UserManager::instance();
        $html_purifier       = \Codendi_HTMLPurifier::instance();
        $permissions_manager = \Docman_PermissionsManager::instance($id);
        $ugroup_manager      = new UGroupManager();
        $builder             = new DocmanServiceRepresentationBuilder(
            new ItemRepresentationBuilder(
                new \Docman_ItemDao(),
                $user_manager,
                Docman_ItemFactory::instance($id),
                $permissions_manager,
                new \Docman_LockFactory(new \Docman_LockDao(), new \Docman_Log()),
                new ApprovalTableStateMapper(),
                new MetadataRepresentationBuilder(
                    new \Docman_MetadataFactory($id),
                    $html_purifier,
                    UserHelper::instance()
                ),
                new ApprovalTableRetriever(
                    new \Docman_ApprovalTableFactoriesFactory(),
                    new Docman_VersionFactory()
                ),
                new DocmanItemPermissionsForGroupsBuilder(
                    $permissions_manager,
                    ProjectManager::instance(),
                    PermissionsManager::instance(),
                    $ugroup_manager
                ),
                $html_purifier
            ),
            $permissions_manager,
            new DocmanServicePermissionsForGroupsBuilder(
                PermissionsManager::instance(),
                $ugroup_manager
            )
        );

        $service_representation = $builder->getServiceRepresentation($project, $user_manager->getCurrentUser());
        if ($service_representation === null) {
            throw new RestException(404);
        }
        return $service_representation;
    }

    /**
     * @url OPTIONS {id}/docman_service
     *
     * @param int $id Id of the project
     */

    public function optionsService(int $id): void
    {
        Header::allowOptionsGet();
    }
}
