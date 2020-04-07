<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_ItemDao;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElementDao;
use Luracast\Restler\RestException;
use PluginManager;
use ProjectManager;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataCollectionBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use URLVerification;
use UserManager;

class ProjectMetadataResource extends AuthenticatedResource
{
    public const RESOURCE_TYPE = 'docman_metadata';
    private const MAX_LIMIT    = 50;

    /**
     * @var Docman_ItemDao
     */
    private $dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->dao             = new Docman_ItemDao();
    }

    /**
     * @url    OPTIONS {id}/docman_metadata
     *
     * @param int $id Id of the project
     *
     * @throws RestException 404
     */
    public function optionsDocman($id): void
    {
        $this->sendAllowHeaders();
    }


    /**
     * Get docman metadata
     *
     * Get metadata of a particular project
     *
     * <br>
     * <pre>
     * "metadata": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"is_required": true,<br>
     *   &nbsp;"is_multiple": true,<br>
     *   &nbsp;"allowed_list_values": "[ <br>
     *   &nbsp; &nbsp;id: 100,<br>
     *   &nbsp; &nbsp;value: "text value"<br>
     *   ]",<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     *
     * <br/>
     *
     * @url    GET {id}/docman_metadata
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\REST\v1\ProjectConfiguredMetadataRepresentation}
     *
     * @throws RestException 404
     */

    public function getDocmanMetadata($id, $limit = 10, $offset = 0): array
    {
        $this->checkAccess();

        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        if (! $docman_plugin->isAllowed($id)) {
            throw new RestException(404, 'Docman plugin not activated');
        }

        $this->sendAllowHeaders();

        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();
        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt($user, $project);

        $result = $this->dao->searchRootItemForGroupId($project->getID());
        if (! $result) {
            throw new RestException(404, 'Project has no document root folder');
        }

        $permissions_manager = \Docman_PermissionsManager::instance($id);
        if (! $permissions_manager->userCanRead($user, $result['item_id'])) {
            throw new RestException(404, 'Document plugin not found');
        }

        $collection_builder = new CustomMetadataCollectionBuilder(
            new Docman_MetadataFactory($id),
            new MetadataListOfValuesElementListBuilder(new Docman_MetadataListOfValuesElementDao())
        );
        $collection         = $collection_builder->build();

        $this->sendPaginationHeaders($limit, $offset, $collection->getTotal());

        return array_slice($collection->getMetadataRepresentations(), $offset, $limit);
    }

    private function sendAllowHeaders(): void
    {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
