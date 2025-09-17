<?php
/**
 * Copyright Enalean (c) 2018 - present. All rights reserved.
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

use Codendi_HTMLPurifier;
use Docman_Item;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_MetadataListOfValuesElementFactory;
use Docman_VersionFactory;
use EventManager;
use Luracast\Restler\RestException;
use PermissionsManager;
use Project;
use ProjectManager;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\Log\LogEntry;
use Tuleap\Docman\Log\LogRetriever;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\Docman\REST\v1\Log\LogEntryRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Docman\REST\v1\Metadata\UnknownMetadataException;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use UGroupManager;
use UserHelper;
use UserManager;

final class DocmanItemsResource extends AuthenticatedResource
{
    public const int MAX_LIMIT = 50;

    private Docman_ItemDao $item_dao;
    private DocmanItemsRequestBuilder $request_builder;
    private EventManager $event_manager;

    public function __construct()
    {
        $this->item_dao        = new Docman_ItemDao();
        $this->request_builder = new DocmanItemsRequestBuilder(UserManager::instance(), ProjectManager::instance());
        $this->event_manager   = EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId(int $id): void
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
     * @param bool $with_size <b>Only for folders</b>. When true, the size of the folder in Bytes is returned in the representation.
     *
     * <div class="tlp-alert-info">
     *     Please note
     *     <ul>
     *         <li>The size of a folder is computed on the documents of type "file", that is to say files and embedded files.</li>
     *         <li>The number of files is the sum of the number of files, embedded files and folders.</li>
     *     </ul>
     * </div>
     *
     * @return ItemRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId($id, bool $with_size = false)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        $items_request = $this->request_builder->buildFromItemId($id);
        $item          = $items_request->getItem();

        if ($with_size === true && ! ($item instanceof \Docman_Folder)) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'with_size = true only works with folders.')
            );
        }

        $representation_visitor = $this->getItemRepresentationVisitor($items_request);
        try {
            return $item->accept(
                $representation_visitor,
                [
                    'current_user' => $items_request->getUser(),
                    'is_a_direct_access' => true,
                    'with_size' => $with_size,
                ]
            );
        } catch (UnknownMetadataException $exception) {
            throw new RestException(
                500,
                $exception->getMessage()
            );
        }
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
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
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

        try {
            $items_representation = $item_representation_builder->buildFolderContent($folder, $user, $limit, $offset);
        } catch (UnknownMetadataException $exception) {
            throw new RestException(
                500,
                $exception->getMessage()
            );
        }

        Header::sendPaginationHeaders($limit, $offset, $items_representation->getTotalSize(), self::MAX_LIMIT);

        return $items_representation->getPaginatedElementCollection();
    }

    /**
     * @url OPTIONS {id}/docman_items
     */
    public function optionsDocumentItems(int $id): void
    {
        $this->sendAllowHeadersWithPost();
    }

    /**
     * @url OPTIONS {id}/parents
     */
    public function optionsParents(int $id): void
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
     * @param int $limit  Number of elements displayed {@from path}{@min 0}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return ItemRepresentation[]
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
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
        $items_representation        = $item_representation_builder->buildParentsItemRepresentation(
            $item,
            $user,
            $limit,
            $offset
        );

        Header::sendPaginationHeaders($limit, $offset, $items_representation->getTotalSize(), self::MAX_LIMIT);

        return $items_representation->getPaginatedElementCollection();
    }

    /**
     * @url OPTIONS {id}/logs
     */
    public function optionsLogs(int $id): void
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get the logs of an item
     *
     * @url    GET {id}/logs
     * @access hybrid
     *
     * @param int $id     Id of the item
     * @param int $limit  Number of elements displayed {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return LogEntryRepresentation[]
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     *
     */
    public function getLogs(int $id, int $limit = self::MAX_LIMIT, int $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeaders();

        $items_request = $this->request_builder->buildFromItemId($id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $user          = $items_request->getUser();

        $docman_permissions_manager = \Docman_PermissionsManager::instance($project->getGroupId());
        $display_access_logs        = $docman_permissions_manager->userCanManage($user, $item->getId());

        $log_retriever = new LogRetriever(
            new \Tuleap\Docman\Log\LogDao(),
            UserManager::instance(),
            new Docman_MetadataListOfValuesElementFactory(),
        );

        $page = $log_retriever->getPaginatedLogForItem($item, $limit, $offset, $display_access_logs);

        Header::sendPaginationHeaders($limit, $offset, $page->total, self::MAX_LIMIT);

        return array_map(
            static fn (LogEntry $entry): LogEntryRepresentation => LogEntryRepresentation::fromEntry($entry, new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash())),
            $page->entries,
        );
    }

    /**
     * @throws I18NRestException
     */
    private function checkItemCanHaveSubitems(\Docman_Item $item)
    {
        $item_checker = new ItemCanHaveSubItemsChecker();
        $item_checker->checkItemCanHaveSubitems($item);
    }

    /**
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

    private function getItemRepresentationVisitor(DocmanItemsRequest $items_request): ItemRepresentationVisitor
    {
        $event_adder = new DocmanItemsEventAdder($this->event_manager);

        return new ItemRepresentationVisitor(
            $this->getItemRepresentationBuilder($items_request->getItem(), $items_request->getProject()),
            new \Docman_VersionFactory(),
            new \Docman_LinkVersionFactory(),
            Docman_ItemFactory::instance($items_request->getProject()->getGroupId()),
            $this->event_manager,
            $event_adder
        );
    }

    private function getItemRepresentationBuilder(Docman_Item $item, Project $project): ItemRepresentationBuilder
    {
        $html_purifier = Codendi_HTMLPurifier::instance();

        $permissions_manager = $this->getDocmanPermissionManager($project);

        return new ItemRepresentationBuilder(
            $this->item_dao,
            \UserManager::instance(),
            Docman_ItemFactory::instance($item->getGroupId()),
            $permissions_manager,
            new \Docman_LockFactory(new \Docman_LockDao(), new \Docman_Log()),
            new ApprovalTableStateMapper(),
            new MetadataRepresentationBuilder(
                new \Docman_MetadataFactory($project->getID()),
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
                new UGroupManager()
            ),
            $html_purifier,
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
    }
}
