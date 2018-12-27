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

use Docman_Item;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_Log;
use EventManager;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Tuleap\Docman\Item\ItemIsNotAFolderException;
use Tuleap\Docman\Log\LogEventAdder;
use Tuleap\Docman\Notifications\NotificationBuilders;
use Tuleap\Docman\Notifications\NotificationEventAdder;
use Tuleap\Docman\Upload\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\DocumentToUploadCreator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanItemsResource extends AuthenticatedResource
{
    const MAX_LIMIT = 50;

    /**
     * @var Docman_ItemDao
     */
    private $item_dao;
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
        $this->item_dao          = new Docman_ItemDao();
        $this->request_builder   = new DocmanItemsRequestBuilder($this->rest_user_manager, ProjectManager::instance());
        $this->event_manager = EventManager::instance();
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
     * @access hybrid
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
        $item          = $items_request->getItem();

        $representation_visitor = $this->getItemRepresentationVisitor($items_request);

        return $item->accept($representation_visitor, ['current_user' => $items_request->getUser()]);
    }

    /**
     * Create new item
     *
     * Warning: only empty and file items can be created.
     *
     * When creating a new file, you will get an URL where the file needs
     * to be uploaded using the
     * <a href="https://tus.io/protocols/resumable-upload.html">tus resumable upload protocol</a>
     * to validate the item creation. You will need to use the same authentication mechanism you used
     * to call this endpoint.
     *
     * @url    POST
     * @status 201
     *
     * @return CreatedItemRepresentation
     *
     * @throws 400
     * @throws 403
     * @throws 404
     * @throws 409
     */
    protected function post(DocmanItemPOSTRepresentation $docman_item_post_representation)
    {
        $this->checkAccess();
        $this->sendAllowHeadersWithPost();

        $current_user = $this->rest_user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($docman_item_post_representation->parent_id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->checkUserCanWriteFolder($current_user, $project, $docman_item_post_representation->parent_id);

        $this->addLogEvents();
        $this->addNotificationEvents($project);

        $document_on_going_upload_dao = new DocumentOngoingUploadDAO();

        $docman_item_creator = new DocmanItemCreator(
            $this->getPermissionManager(),
            $this->event_manager,
            $this->getItemFactory($project->getID()),
            new DocumentOngoingUploadRetriever($document_on_going_upload_dao),
            new DocumentToUploadCreator($document_on_going_upload_dao)
        );

        return $docman_item_creator->create(
            $parent,
            $current_user,
            $project,
            $docman_item_post_representation,
            new \DateTimeImmutable()
        );
    }

    private function getItemFactory($group_id = null)
    {
        return new Docman_ItemFactory($group_id);
    }

    /**
     * Get the content of a folder
     *
     * @url    GET {id}/docman_items
     * @access hybrid
     *
     * @param int $id     Id of the folder
     * @param int $limit  Number of elements displayed {@from path}{@min 0}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
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

        $this->sendAllowHeadersWithPost();

        $items_request = $this->request_builder->buildFromItemId($id);
        $folder        = $items_request->getItem();
        $this->checkItemCanHaveSubitems($folder);

        $user = $items_request->getUser();

        $item_representation_builder = $this->getRepresentationBuilder($items_request);

        $items_representation = $item_representation_builder->buildFolderContent($folder, $user, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $items_representation->getTotalSize(), self::MAX_LIMIT);

        return $items_representation->getPaginatedElementCollection();
    }

    /**
     * @url OPTIONS {id}/docman_items
     */
    public function optionsDocumentItems($id)
    {
        $this->sendAllowHeadersWithPost();
    }

    /**
     * @url OPTIONS {id}/parents
     */
    public function optionsParents($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get the parents of an item
     *
     * Get the parents of an item order by folder hierarchy
     * Given Folder A > Folder B > Item
     * Then sorted parents of Item are Folder A > Folder
     *
     * @url    GET {id}/parents
     * @access hybrid
     *
     * @param int $id     Id of the item
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
    public function getParents($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        $items_request = $this->request_builder->buildFromItemId($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $item_representation_builder = $this->getRepresentationBuilder($items_request);
        $items_representation        = $item_representation_builder->buildParents($item, $user, $project, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $items_representation->getTotalSize(), self::MAX_LIMIT);

        return $items_representation->getPaginatedElementCollection();
    }

    /**
     * @throws I18NRestException
     */
    private function checkItemCanHaveSubitems(\Docman_Item $item)
    {
        $visitor = new ItemCanHaveSubitemsCheckerVisitor();
        try {
            $item->accept($visitor, []);
        } catch (ItemIsNotAFolderException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The item %d is not a folder.'),
                    $item->getId()
                )
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

    private function sendAllowHeadersWithPost()
    {
        Header::allowOptionsGetPost();
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }

    /**
     * @return ItemRepresentationCollectionBuilder
     */
    private function getRepresentationBuilder(DocmanItemsRequest $items_request)
    {
        return new ItemRepresentationCollectionBuilder(
            $items_request->getFactory(),
            $this->getDocmanPermissionManager($items_request->getProject()),
            $this->getItemRepresentationVisitor($items_request),
            $this->item_dao
        );
    }

    /**
     *
     * @return ItemRepresentationVisitor
     */
    private function getItemRepresentationVisitor(DocmanItemsRequest $items_request)
    {
        return new ItemRepresentationVisitor(
            $this->getItemRepresentationBuilder($items_request->getItem(), $items_request->getProject()),
            new \Docman_VersionFactory(),
            new \Docman_LinkVersionFactory()
        );
    }

    private function getPermissionManager()
    {
        return \PermissionsManager::instance();
    }

    private function getItemRepresentationBuilder(Docman_Item $item, Project $project)
    {
        $item_representation_builder = new ItemRepresentationBuilder(
            $this->item_dao,
            \UserManager::instance(),
            Docman_ItemFactory::instance($item->getGroupId()),
            $this->getDocmanPermissionManager($project)
        );
        return $item_representation_builder;
    }

    /**
     * @throws I18NRestException
     */
    private function checkUserCanWriteFolder(\PFUser $current_user, Project $project, $folder_id)
    {
        $docman_permissions_manager = $this->getDocmanPermissionManager($project);
        if (!$docman_permissions_manager->userCanWrite($current_user, $folder_id)) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-docman', "You are not allowed to write on folder with id '%d'"),
                    $folder_id
                )
            );
        }
    }

    private function addNotificationEvents(Project $project)
    {
        $feedback                         = new NullResponseFeedbackWrapper();
        $notifications_builders           = new NotificationBuilders($feedback, $project, null);
        $notification_manager             = $notifications_builders->buildNotificationManager();
        $notification_manager_add         = $notifications_builders->buildNotificationManagerAdd();
        $notification_manager_delete      = $notifications_builders->buildNotificationManagerDelete();
        $notification_manager_move        = $notifications_builders->buildNotificationManagerMove();
        $notification_manager_subscribers = $notifications_builders->buildNotificationManagerSubsribers();

        $adder = new NotificationEventAdder(
            $this->event_manager,
            $notification_manager,
            $notification_manager_add,
            $notification_manager_delete,
            $notification_manager_move,
            $notification_manager_subscribers
        );


        $adder->addNotificationManagement();
    }

    private function addLogEvents()
    {
        $logger = new Docman_Log();
        $adder =  new LogEventAdder($this->event_manager, $logger);

        $adder->addLogEventManagement();
    }
}
