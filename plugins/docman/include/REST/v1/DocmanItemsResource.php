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
use ProjectManager;
use Tuleap\Docman\Item\ItemIsNotAFolderException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
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
    /**
     * @var DocmanItemsRequestBuilder
     */
    private $request_builder;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->item_dao        = new Docman_ItemDao();
        $this->request_builder = new DocmanItemsRequestBuilder($this->user_manager, ProjectManager::instance());
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get item
     *
     * @url    GET {id}
     *
     * @access protected
     *
     * @param int $id Id of the folder
     *
     * @return ItemRepresentation
     *
     * @throws 400
     * @throws 403
     * @throws 404
     */
    public function getId($id)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        $items_request = $this->request_builder->buildFromItemId($id);
        $item_factory  = $items_request->getFactory();
        $item          = $items_request->getItem();

        $representation_visitor = new ItemRepresentationVisitor(
            new ItemRepresentationBuilder(
                $this->item_dao,
                $this->user_manager,
                $item_factory
            )
        );

        return $item->accept($representation_visitor);
    }


    /**
     * @url OPTIONS {id}/docman_items
     */
    public function optionsDocumentItems($id)
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
     * @throws 400
     * @throws 403
     * @throws 404
     */
    public function getDocumentItems($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        $items_request = $this->request_builder->buildFromItemId($id);
        $folder        = $items_request->getItem();
        $this->checkItemCanHaveSubitems($folder);

        $item_factory  = $items_request->getFactory();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

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
    private function checkItemCanHaveSubitems(\Docman_Item $item)
    {
        $visitor = new ItemCanHaveSubitemsCheckerVisitor();
        try {
            $item->accept($visitor, []);
        } catch (ItemIsNotAFolderException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'The item is not a folder.')
            );
        }
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
