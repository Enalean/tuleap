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

use Docman_ItemFactory;
use Docman_PermissionsManager;
use EventManager;
use Luracast\Restler\RestException;
use PermissionsManager;
use Project;
use ProjectManager;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdatorBuilder;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataRepresentation;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetRepresentation;
use Tuleap\Docman\REST\v1\Permissions\PermissionItemUpdaterFromRESTContext;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use UGroupManager;
use UserManager;

class DocmanOtherTypeDocumentsResource extends AuthenticatedResource
{
    /**
     * @var \EventManager
     */
    private $event_manager;
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
        $this->request_builder = new DocmanItemsRequestBuilder($this->user_manager, ProjectManager::instance());
        $this->event_manager   = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsDelete();
    }

    /**
     * Delete an existing other type document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the document
     *
     * @status 200
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws RestException 404
     */
    public function delete(int $id): void
    {
        $this->checkAccess();

        $item_request   = $this->request_builder->buildFromItemId($id);
        $item_to_delete = $item_request->getItem();
        $current_user   = $this->user_manager->getCurrentUser();
        $project        = $item_request->getProject();
        $validator      = $this->getValidator($project, $current_user, $item_to_delete);
        $item_to_delete->accept($validator);

        $this->addAllEvent($item_request->getProject());

        try {
            (new \Docman_ItemFactory())->deleteSubTree($item_to_delete, $current_user, false);
        } catch (DeleteFailedException $exception) {
            throw new I18NRestException(
                403,
                $exception->getI18NExceptionMessage()
            );
        }

        $this->event_manager->processEvent('send_notifications', []);
    }

    /**
     * @url OPTIONS {id}/metadata
     */
    public function optionsMetadata(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Update the other type document metadata
     *
     * @url    PUT {id}/metadata
     * @access hybrid
     *
     * @param int                       $id             Id of the other type document
     * @param PUTMetadataRepresentation $representation {@from body}
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws RestException 404
     */
    public function putMetadata(
        int $id,
        PUTMetadataRepresentation $representation,
    ): void {
        $this->checkAccess();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();

        $current_user = $this->user_manager->getCurrentUser();

        $project = $item_request->getProject();

        if (! $this->getPermissionManager($project)->userCanUpdateItemProperties($current_user, $item)) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'You are not allowed to write this item.')
            );
        }

        $validator = $this->getValidator($project, $current_user, $item);
        $item->accept($validator, []);

        $this->addAllEvent($project);

        $updator = MetadataUpdatorBuilder::build($project, $this->event_manager);
        $updator->updateDocumentMetadata(
            $representation,
            $item,
            $current_user
        );
    }

    /**
     * @url OPTIONS {id}/permissions
     */
    public function optionsPermissions(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Update permissions of the document
     *
     * @url    PUT {id}/permissions
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param DocmanItemPermissionsForGroupsSetRepresentation $representation {@from body}
     *
     * @status 200
     *
     * @throws RestException 400
     */
    public function putPermissions(int $id, DocmanItemPermissionsForGroupsSetRepresentation $representation): void
    {
        $this->checkAccess();

        $request_builder = new DocmanItemsRequestBuilder(UserManager::instance(), ProjectManager::instance());

        $item_request = $request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $item         = $item_request->getItem();
        $user         = $item_request->getUser();

        $validator = new DocumentBeforeModificationValidatorVisitor(
            $this->getPermissionManager($project),
            $user,
            $item,
            new DoesItemHasExpectedTypeVisitor(OtherDocument::class),
        );

        $item->accept($validator);

        $this->addAllEvent($project);

        $docman_permission_manager     = $this->getPermissionManager($project);
        $ugroup_manager                = new UGroupManager();
        $permissions_rest_item_updater = new PermissionItemUpdaterFromRESTContext(
            new PermissionItemUpdater(
                new NullResponseFeedbackWrapper(),
                Docman_ItemFactory::instance((int) $project->getID()),
                $docman_permission_manager,
                PermissionsManager::instance(),
                EventManager::instance(),
            ),
            $docman_permission_manager,
            new DocmanItemPermissionsForGroupsSetFactory(
                $ugroup_manager,
                new UserGroupRetriever($ugroup_manager),
                ProjectManager::instance()
            )
        );
        $permissions_rest_item_updater->updateItemPermissions($item, $user, $representation);
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function getPermissionManager(\Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function getValidator(Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor(
            $this->getPermissionManager($project),
            $current_user,
            $item,
            new DoesItemHasExpectedTypeVisitor(OtherDocument::class),
        );
    }

    private function addAllEvent(\Project $project): void
    {
        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);
    }
}
