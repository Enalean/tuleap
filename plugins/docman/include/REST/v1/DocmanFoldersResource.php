<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_PermissionsManager;
use EventManager;
use Project;
use ProjectManager;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanItemCreatorBuilder;
use Tuleap\Docman\REST\v1\Folders\DocmanPOSTFilesRepresentation;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanFoldersResource extends AuthenticatedResource
{
    /**
     * @var RestUserManager
     */
    private $rest_user_manager;
    /**
     * @var DocmanItemsRequestBuilder
     */
    private $request_builder;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct()
    {
        $this->rest_user_manager = RestUserManager::build();
        $this->request_builder   = new DocmanItemsRequestBuilder($this->rest_user_manager, ProjectManager::instance());
        $this->event_manager     = EventManager::instance();
    }

    /**
     * Create new file
     *
     * You will get an URL where the file needs to be uploaded using the
     * <a href="https://tus.io/protocols/resumable-upload.html">tus resumable upload protocol</a>
     * to validate the item creation. You will need to use the same authentication mechanism you used
     * to call this endpoint.
     * <br/>
     * <br/>
     *
     * @param int  $id     Id of the parent folder
     * @param DocmanPOSTFilesRepresentation  $files_representation {@from body} {@type \Tuleap\Docman\REST\v1\Folders\DocmanPOSTFilesRepresentation}
     *
     * @url    POST {id}/files
     * @access hybrid
     * @status 201
     *
     * @return CreatedItemRepresentation
     *
     * @throws 400
     * @throws 403
     * @throws 404
     * @throws 409
     */
    public function postFiles(int $id, DocmanPOSTFilesRepresentation $files_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->sendAllowHeadersWithPost();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
             ->checkUserCanWriteFolder($current_user, $id);

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        $docman_item_creator = DocmanItemCreatorBuilder::build($project);

        return $docman_item_creator->createFileDocument(
            $parent,
            $current_user,
            $files_representation->title,
            $files_representation->description,
            new \DateTimeImmutable(),
            $files_representation->file_properties
        );
    }

    /**
     * Create new folder
     *
     * @param int  $id     Id of the parent folder
     * @param DocmanFolderPOSTRepresentation  $test representation test {@from body} {@type \Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation}
     *
     * @url    POST {id}/folders
     * @access hybrid
     * @status 201
     *
     * @return CreatedItemRepresentation
     *
     * @throws 400
     * @throws 403
     * @throws 404
     * @throws 409
     */
    public function postFolders(int $id, DocmanFolderPOSTRepresentation $folder_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->sendAllowHeadersWithPost();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
             ->checkUserCanWriteFolder($current_user, $id);

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        $docman_item_creator = DocmanItemCreatorBuilder::build($project);

        return $docman_item_creator->createFolder(
            $parent,
            $current_user,
            $folder_representation,
            new \DateTimeImmutable(),
            $project
        );
    }

    private function sendAllowHeadersWithPost()
    {
        Header::allowOptionsPost();
    }

    /**
     * @throws I18NRestException
     */
    private function checkItemCanHaveSubitems(Docman_Item $item): void
    {
        $item_checker = new ItemCanHaveSubItemsChecker();
        $item_checker->checkItemCanHaveSubitems($item);
    }

    private function getDocmanFolderPermissionChecker(Project $project): DocmanFolderPermissionChecker
    {
        return new DocmanFolderPermissionChecker($this->getDocmanPermissionManager($project));
    }

    private function getDocmanPermissionManager(Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }
}
