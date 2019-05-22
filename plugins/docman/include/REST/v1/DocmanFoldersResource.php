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
use Luracast\Restler\RestException;
use PluginManager;
use Project;
use ProjectManager;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanPOSTFilesRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanEmptyPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanItemCreatorBuilder;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
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
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * The format of the obsolescence date is : "YYYY-MM-DD"
     *
     * You will get an URL where the file needs to be uploaded using the
     * <a href="https://tus.io/protocols/resumable-upload.html">tus resumable upload protocol</a>
     * to validate the item creation. You will need to use the same authentication mechanism you used
     * to call this endpoint.
     * <br/>
     * <br/>
     *
     * @param int  $id     Id of the parent folder
     * @param DocmanPOSTFilesRepresentation  $files_representation {@from body} {@type \Tuleap\Docman\REST\v1\Files\DocmanPOSTFilesRepresentation}
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
        $this->setHeaders();

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
        try {
            return $docman_item_creator->createFileDocument(
                $parent,
                $current_user,
                $files_representation->title,
                $files_representation->description,
                $files_representation->status,
                $files_representation->obsolescence_date,
                new \DateTimeImmutable(),
                $files_representation->file_properties
            );
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The "Status" property is not activated for this item.'
                )
            );
        } catch (Metadata\InvalidDateComparisonException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The obsolescence date is before the current date'
                )
            );
        } catch (Metadata\InvalidDateTimeFormatException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The date format is incorrect. The format must be "YYYY-MM-DD"'
                )
            );
        } catch (Metadata\ObsolescenceDateDisabledException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The project does not support obsolescence date, you should not provide it to create a new document.'
                )
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $files_representation->status
                )
            );
        } catch (Metadata\StatusNotFoundNullException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'null is not a valid status.')
            );
        }
    }

    /**
     * Create new folder
     *
     * @param int  $id     Id of the parent folder
     * @param DocmanFolderPOSTRepresentation  $folder_representation {@from body} {@type \Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation}
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
        $this->setHeaders();

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

        try {
            return $docman_item_creator->createFolder(
                $parent,
                $current_user,
                $folder_representation,
                new \DateTimeImmutable(),
                $project
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $folder_representation->status
                )
            );
        } catch (Metadata\StatusNotFoundNullException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'null is not a valid status.')
            );
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The "Status" property is not activated for this item.'
                )
            );
        }
    }

    /**
     * Create new empty document
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * The format of the obsolescence date is : "YYYY-MM-DD"
     *
     * @param int                                 $id   Id of the parent folder
     * @param DocmanEmptyPOSTRepresentation $empty_representation {@from body} {@type \Tuleap\Docman\REST\v1\Folders\DocmanEmptyPOSTRepresentation}
     *
     * @url    POST {id}/empties
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
    public function postEmpties(int $id, DocmanEmptyPOSTRepresentation $empty_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setHeaders();

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

        try {
            return $docman_item_creator->createEmpty(
                $parent,
                $current_user,
                $empty_representation,
                new \DateTimeImmutable(),
                $project
            );
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The "Status" property is not activated for this item.'
                )
            );
        } catch (Metadata\InvalidDateComparisonException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The obsolescence date is before the current date'
                )
            );
        } catch (Metadata\InvalidDateTimeFormatException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The date format is incorrect. The format must be "YYYY-MM-DD"'
                )
            );
        } catch (Metadata\ObsolescenceDateDisabledException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The project does not support obsolescence date, you should not provide it to create a new document.'
                )
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $empty_representation->status
                )
            );
        } catch (Metadata\StatusNotFoundNullException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'null is not a valid status.')
            );
        }
    }

    /**
     * Create new wiki document
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * The format of the obsolescence date is : "YYYY-MM-DD"
     *
     * @param int                                 $id   Id of the parent folder
     * @param DocmanWikiPOSTRepresentation $wiki_representation {@from body} {@type \Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation}
     *
     * @url    POST {id}/wikis
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
    public function postWikis(int $id, DocmanWikiPOSTRepresentation $wiki_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setHeaders();

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
        try {
            return $docman_item_creator->createWiki(
                $parent,
                $current_user,
                $wiki_representation,
                new \DateTimeImmutable(),
                $project
            );
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The "Status" property is not activated for this item.'
                )
            );
        } catch (Metadata\InvalidDateComparisonException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The obsolescence date is before the current date'
                )
            );
        } catch (Metadata\InvalidDateTimeFormatException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The date format is incorrect. The format must be "YYYY-MM-DD"'
                )
            );
        } catch (Metadata\ObsolescenceDateDisabledException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The project does not support obsolescence date, you should not provide it to create a new document.'
                )
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $wiki_representation->status
                )
            );
        } catch (Metadata\StatusNotFoundNullException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'null is not a valid status.')
            );
        }
    }

    /**
     * Create new embedded document
     *
     * @param int                              $id   Id of the parent folder
     * @param DocmanEmbeddedPOSTRepresentation $embeds_representation {@from body}
     *                                               {@type \Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation}
     *
     * @url    POST {id}/embedded_files
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
    public function postEmbeds(int $id, DocmanEmbeddedPOSTRepresentation $embeds_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setHeaders();

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

        /** @var \DocmanPlugin $docman_plugin */
        $docman_plugin        = PluginManager::instance()->getPluginByName('docman');
        $are_embedded_allowed = $docman_plugin->getPluginInfo()->getPropertyValueForName('embedded_are_allowed');

        if ($are_embedded_allowed === false) {
            throw new RestException(403, 'Embedded files are not allowed');
        }

        try {
            return $docman_item_creator->createEmbedded(
                $parent,
                $current_user,
                $embeds_representation,
                new \DateTimeImmutable(),
                $project
            );
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The "Status" property is not activated for this item.'
                )
            );
        } catch (Metadata\InvalidDateComparisonException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The obsolescence date is before the current date'
                )
            );
        } catch (Metadata\InvalidDateTimeFormatException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The date format is incorrect. The format must be "YYYY-MM-DD"'
                )
            );
        } catch (Metadata\ObsolescenceDateDisabledException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The project does not support obsolescence date, you should not provide it to create a new document.'
                )
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $embeds_representation->status
                )
            );
        } catch (Metadata\StatusNotFoundNullException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'null is not a valid status.')
            );
        }
    }

    /**
     * Create new link document
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * The format of the obsolescence date is : "YYYY-MM-DD"
     *
     * @param int                              $id   Id of the parent folder
     * @param DocmanLinkPOSTRepresentation $links_representation {@from body}
     *                                               {@type \Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation}
     *
     * @url    POST {id}/links
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
    public function postLinks(int $id, DocmanLinkPOSTRepresentation $links_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setHeaders();

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

        try {
            return $docman_item_creator->createLink(
                $parent,
                $current_user,
                $links_representation,
                new \DateTimeImmutable(),
                $project
            );
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The "Status" property is not activated for this item.'
                )
            );
        } catch (Metadata\InvalidDateComparisonException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The obsolescence date is before the current date'
                )
            );
        } catch (Metadata\InvalidDateTimeFormatException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The date format is incorrect. The format must be "YYYY-MM-DD"'
                )
            );
        } catch (Metadata\ObsolescenceDateDisabledException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The project does not support obsolescence date, you should not provide it to create a new document.'
                )
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $links_representation->status
                )
            );
        } catch (Metadata\StatusNotFoundNullException $e) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-docman', 'null is not a valid status.')
            );
        }
    }

    /**
     * Delete a folder in the document manager
     *
     * Delete an existing folder and its content
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the folder
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws RestException 401
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     */
    public function delete(int $id) : void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request        = $this->request_builder->buildFromItemId($id);
        $item_to_delete      = $item_request->getItem();
        $current_user        = $this->rest_user_manager->getCurrentUser();
        $project             = $item_request->getProject();
        $validator_visitor   = new DocumentBeforeModificationValidatorVisitor(\Docman_Folder::class);

        $item_to_delete->accept($validator_visitor);

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        if ($item_to_delete->getParentId() === 0) {
            throw new i18NRestException(400, dgettext("tuleap-docman", "You cannot delete the root folder."));
        }

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

    private function setHeaders()
    {
        Header::allowOptionsPostDelete();
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
