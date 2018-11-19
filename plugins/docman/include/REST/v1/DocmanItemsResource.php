<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Docman\REST\v1;

use Docman_ItemDao;
use Luracast\Restler\RestException;
use Tuleap\Docman\Item\ItemIsNotAFolderException;
use Tuleap\Request\ForbiddenException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use URLVerification;
use UserManager;

class DocmanItemsResource extends AuthenticatedResource
{
    const MAX_LIMIT = 50;

    /**
     * @var Docman_ItemDao
     */
    private $item_dao;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager = $this->getUserManager();
        $this->item_dao     = new Docman_ItemDao();
    }

    /**
     * @url OPTIONS {id}
     */
    public function options($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get the content of a folder
     *
     * @url    GET {id}/docman_items
     *
     * @access protected
     *
     * @param int $id     Id of the folder
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     * @param int $limit  Number of elements displayed {@from path}{@min 0}{@max 50}
     *
     * @return ItemRepresentation[]
     *
     * @status 200
     * @throws 400
     * @throws 403
     * @throws 404
     *
     */
    public function getDocumentItems($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();

        $this->sendAllowHeaders();
        $item_factory = \Docman_ItemFactory::instance($id);
        $folder         = $item_factory->getItemFromDb($id);

        if ($folder === null) {
            throw new RestException(
                404,
                null,
                ['i18n_error_message' => dgettext('tuleap-docman', 'The folder does not exist.')]
            );
        }

        $project = $this->getProjectFromItem($folder);


        $user = $this->user_manager->getCurrentUser();

        $this->checkProjectAccessibility($project, $user);
        $this->checkFolderAccessibility($folder, $project, $user);

        $item_representation_builder = new ItemRepresentationCollectionBuilder(
            $item_factory,
            $this->getDocmanPermissionManager($project),
            new ItemRepresentationVisitor(
                new ItemRepresentationBuilder(
                    $this->item_dao,
                    $this->user_manager,
                    $item_factory
                )
            ),
            $this->item_dao
        );

        $items_representation = $item_representation_builder->buildFolderContent($folder, $user, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $items_representation->getTotalSize(), self::MAX_LIMIT);

        return $items_representation->getPaginatedElementCollection();
    }

    /**
     * @throws RestException
     */
    private function checkProjectAccessibility(\Project $project, \PFUser $user)
    {
        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt($user, $project);
    }

    /**
     * @return UserManager
     */
    private function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * @throws RestException
     */
    private function checkFolderAccessibility(\Docman_Item $item, \Project $project, \PFUser $user)
    {
        $visitor = new FolderAccessibilityCheckerVisitor($this->getDocmanPermissionManager($project));
        try {
            $item->accept($visitor, ["user" => $user]);
        } catch (ItemIsNotAFolderException $e) {
            throw new RestException(
                400,
                null,
                ['i18n_error_message' => dgettext('tuleap-docman', 'The item is not a folder.')]
            );
        } catch (ForbiddenException $e) {
            throw new RestException(
                403,
                null,
                ['i18n_error_message' => dgettext('tuleap-docman', 'Permission denied')]
            );
        }
    }

    /**
     * @return \Project
     */
    private function getProjectFromItem(\Docman_Item $item)
    {
        $project_manager = \ProjectManager::instance();
        $project         = $project_manager->getProject($item->getGroupId());
        return $project;
    }

    /**
     * @param \Project $project
     *
     * @return \Docman_PermissionsManager
     */
    private function getDocmanPermissionManager(\Project $project)
    {
        return \Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
