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

use DateTimeImmutable;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersionFactory;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElementDao;
use Docman_PermissionsManager;
use Docman_Wiki;
use DocmanPlugin;
use EventManager;
use Luracast\Restler\RestException;
use PermissionsManager;
use PluginManager;
use Project;
use ProjectManager;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Docman\Metadata\MetadataFactoryBuilder;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\CopyItem\BeforeCopyVisitor;
use Tuleap\Docman\REST\v1\CopyItem\DocmanItemCopier;
use Tuleap\Docman\REST\v1\CopyItem\DocmanValidateRepresentationForCopy;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation;
use Tuleap\Docman\REST\v1\Empties\DocmanEmptyPOSTRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanPOSTFilesRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanItemCreatorBuilder;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataCollectionBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataRepresentationRetriever;
use Tuleap\Docman\REST\v1\Metadata\MetadataUpdatorBuilder;
use Tuleap\Docman\REST\v1\Metadata\PUTMetadataFolderRepresentation;
use Tuleap\Docman\REST\v1\MoveItem\BeforeMoveVisitor;
use Tuleap\Docman\REST\v1\MoveItem\DocmanItemMover;
use Tuleap\Docman\REST\v1\Permissions\DocmanFolderPermissionsForGroupsPUTRepresentation;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\PermissionItemUpdaterFromRESTContext;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use UGroupManager;
use UserManager;

class DocmanFoldersResource extends AuthenticatedResource
{
    /**
     * @var UserManager
     */
    private $user_manager;
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
        $this->user_manager    = UserManager::instance();
        $this->request_builder = new DocmanItemsRequestBuilder($this->user_manager, ProjectManager::instance());
        $this->event_manager   = EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function options(int $id): void
    {
        $this->setHeaders();
    }

    /**
     * @url OPTIONS {id}/files
     * @url OPTIONS {id}/folders
     * @url OPTIONS {id}/empties
     * @url OPTIONS {id}/wikis
     * @url OPTIONS {id}/embedded_files
     * @url OPTIONS {id}/links
     */
    public function optionsCreation(int $id): void
    {
        $this->setCreationHeaders();
    }

    /**
     * Create new file document
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
     *
     * @throws I18NRestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    public function postFiles(int $id, DocmanPOSTFilesRepresentation $files_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setCreationHeaders();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
            ->checkUserCanWriteFolder($current_user, $id);

        $this->addAllEvent($project);

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($files_representation)) {
            $docman_item_creator = DocmanItemCreatorBuilder::build($project);

            try {
                $metadata_factory    = new Docman_MetadataFactory($project->getGroupId());
                $list_values_builder = new MetadataListOfValuesElementListBuilder(
                    new Docman_MetadataListOfValuesElementDao()
                );
                $custom_retriever    = new CustomMetadataRepresentationRetriever(
                    $metadata_factory,
                    $list_values_builder,
                    new CustomMetadataCollectionBuilder($metadata_factory, $list_values_builder)
                );

                $metadata_to_create = $custom_retriever->checkAndRetrieveFileFormattedRepresentation($parent, $files_representation->metadata);

                return $docman_item_creator->createFileDocument(
                    $parent,
                    $current_user,
                    $files_representation->title,
                    $files_representation->description,
                    $files_representation->status,
                    $files_representation->obsolescence_date,
                    new DateTimeImmutable(),
                    $files_representation->file_properties,
                    $metadata_to_create,
                    $files_representation->permissions_for_groups
                );
            } catch (Metadata\HardCodedMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            } catch (CustomMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            }
        }
        if ($representation_for_copy_validation->isValidAsACopyRepresentation($files_representation)) {
            return $this->getItemCopier($project, Docman_File::class)->copyItem(
                new DateTimeImmutable(),
                $parent,
                $current_user,
                $files_representation->copy
            );
        }
        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create a file document (the properties %s are required for the creation)',
                implode(', ', $files_representation::getNonCopyRequiredObjectProperties())
            )
        );
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
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    public function postFolders(int $id, DocmanFolderPOSTRepresentation $folder_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setCreationHeaders();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();

        $this->addAllEvent($project);

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($folder_representation)) {
            $docman_item_creator = DocmanItemCreatorBuilder::build($project);
            try {
                return $docman_item_creator->createFolder(
                    $parent,
                    $current_user,
                    $folder_representation,
                    new DateTimeImmutable(),
                    $project
                );
            } catch (Metadata\HardCodedMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            } catch (CustomMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            }
        }
        if ($representation_for_copy_validation->isValidAsACopyRepresentation($folder_representation)) {
            return $this->getItemCopier($project, Docman_Folder::class)->copyItem(
                new DateTimeImmutable(),
                $parent,
                $current_user,
                $folder_representation->copy
            );
        }
        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create a folder (the properties %s are required for the creation)',
                implode(', ', $folder_representation::getNonCopyRequiredObjectProperties())
            )
        );
    }

    /**
     * Create new empty document
     *
     * The format of the obsolescence date is : "YYYY-MM-DD"
     *
     * @param int                                 $id   Id of the parent folder
     * @param DocmanEmptyPOSTRepresentation $empty_representation {@from body} {@type \Tuleap\Docman\REST\v1\Empties\DocmanEmptyPOSTRepresentation}
     *
     * @url    POST {id}/empties
     * @access hybrid
     * @status 201
     *
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    public function postEmpties(int $id, DocmanEmptyPOSTRepresentation $empty_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setCreationHeaders();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
            ->checkUserCanWriteFolder($current_user, $id);

        $this->addAllEvent($project);

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($empty_representation)) {
            $docman_item_creator = DocmanItemCreatorBuilder::build($project);
            try {
                return $docman_item_creator->createEmpty(
                    $parent,
                    $current_user,
                    $empty_representation,
                    new DateTimeImmutable(),
                    $project
                );
            } catch (Metadata\HardCodedMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            } catch (CustomMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            }
        }
        if ($representation_for_copy_validation->isValidAsACopyRepresentation($empty_representation)) {
            return $this->getItemCopier($project, Docman_Empty::class)->copyItem(
                new DateTimeImmutable(),
                $parent,
                $current_user,
                $empty_representation->copy
            );
        }
        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create an empty document (the properties %s are required for the creation)',
                implode(', ', $empty_representation::getNonCopyRequiredObjectProperties())
            )
        );
    }

    /**
     * Create new wiki document
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
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    public function postWikis(int $id, DocmanWikiPOSTRepresentation $wiki_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setCreationHeaders();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
            ->checkUserCanWriteFolder($current_user, $id);

        $this->addAllEvent($project);

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($wiki_representation)) {
            $docman_item_creator = DocmanItemCreatorBuilder::build($project);
            try {
                return $docman_item_creator->createWiki(
                    $parent,
                    $current_user,
                    $wiki_representation,
                    new DateTimeImmutable(),
                    $project
                );
            } catch (Metadata\HardCodedMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            } catch (CustomMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            }
        }
        if ($representation_for_copy_validation->isValidAsACopyRepresentation($wiki_representation)) {
            return $this->getItemCopier($project, Docman_Wiki::class)->copyItem(
                new DateTimeImmutable(),
                $parent,
                $current_user,
                $wiki_representation->copy
            );
        }
        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create a wiki document (the properties %s are required for the creation)',
                implode(', ', $wiki_representation::getNonCopyRequiredObjectProperties())
            )
        );
    }

    /**
     * Create new embedded document
     *
     * @param int                              $id   Id of the parent folder
     * @param DocmanEmbeddedPOSTRepresentation $embeds_representation {@from body}
     *
     * @url    POST {id}/embedded_files
     * @access hybrid
     * @status 201
     *
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    public function postEmbeds(int $id, DocmanEmbeddedPOSTRepresentation $embeds_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setCreationHeaders();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
            ->checkUserCanWriteFolder($current_user, $id);

        $this->addAllEvent($project);

        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        assert($docman_plugin instanceof DocmanPlugin);
        $docman_plugin_info   = $docman_plugin->getPluginInfo();
        $are_embedded_allowed = $docman_plugin_info->getPropertyValueForName('embedded_are_allowed');

        if ($are_embedded_allowed === false) {
            throw new RestException(403, 'Embedded files are not allowed');
        }

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($embeds_representation)) {
            try {
                $docman_item_creator = DocmanItemCreatorBuilder::build($project);
                return $docman_item_creator->createEmbedded(
                    $parent,
                    $current_user,
                    $embeds_representation,
                    new DateTimeImmutable(),
                    $project
                );
            } catch (Metadata\HardCodedMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            } catch (CustomMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            }
        }
        if ($representation_for_copy_validation->isValidAsACopyRepresentation($embeds_representation)) {
            return $this->getItemCopier($project, Docman_EmbeddedFile::class)->copyItem(
                new DateTimeImmutable(),
                $parent,
                $current_user,
                $embeds_representation->copy
            );
        }
        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create an embedded document (the properties %s are required for the creation)',
                implode(', ', $embeds_representation::getNonCopyRequiredObjectProperties())
            )
        );
    }

    /**
     * Create new link document
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
     *
     * @throws I18NRestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    public function postLinks(int $id, DocmanLinkPOSTRepresentation $links_representation): CreatedItemRepresentation
    {
        $this->checkAccess();
        $this->setCreationHeaders();

        $current_user = $this->user_manager->getCurrentUser();

        $item_request = $this->request_builder->buildFromItemId($id);
        $parent       = $item_request->getItem();
        $this->checkItemCanHaveSubitems($parent);
        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
            ->checkUserCanWriteFolder($current_user, $id);

        $this->addAllEvent($project);

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($links_representation)) {
            $docman_item_creator = DocmanItemCreatorBuilder::build($project);
            try {
                return $docman_item_creator->createLink(
                    $parent,
                    $current_user,
                    $links_representation,
                    new DateTimeImmutable(),
                    $project
                );
            } catch (Metadata\HardCodedMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            } catch (CustomMetadataException $e) {
                throw new I18NRestException(
                    400,
                    $e->getI18NExceptionMessage()
                );
            }
        }
        if ($representation_for_copy_validation->isValidAsACopyRepresentation($links_representation)) {
            return $this->getItemCopier($project, Docman_Link::class)->copyItem(
                new DateTimeImmutable(),
                $parent,
                $current_user,
                $links_representation->copy
            );
        }
        throw new RestException(
            400,
            sprintf(
                'You need to either copy or create a link document (the properties %s are required for the creation)',
                implode(', ', $links_representation::getNonCopyRequiredObjectProperties())
            )
        );
    }

    /**
     * Move an existing folder
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                           $id             ID of the folder
     * @param DocmanPATCHItemRepresentation $representation {@from body}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */

    public function patch(int $id, DocmanPATCHItemRepresentation $representation): void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $this->addAllEvent($project);

        $item_factory = new Docman_ItemFactory();
        $item_mover   = new DocmanItemMover(
            $item_factory,
            new BeforeMoveVisitor(
                new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
                $item_factory,
                new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO())
            ),
            $this->getPermissionManager($project),
            $this->event_manager
        );

        $item_mover->moveItem(
            new DateTimeImmutable(),
            $item_request->getItem(),
            UserManager::instance()->getCurrentUser(),
            $representation->move
        );
    }

    /**
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
    public function delete(int $id): void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request      = $this->request_builder->buildFromItemId($id);
        $item_to_delete    = $item_request->getItem();
        $current_user      = $this->user_manager->getCurrentUser();
        $project           = $item_request->getProject();
        $validator_visitor = $this->getValidator($project, $current_user, $item_to_delete);

        $item_to_delete->accept($validator_visitor);

        $this->addAllEvent($project);

        if ($item_to_delete->getParentId() === 0) {
            throw new I18NRestException(400, dgettext("tuleap-docman", "You cannot delete the root folder."));
        }

        try {
            (new Docman_ItemFactory())->deleteSubTree($item_to_delete, $current_user, false);
        } catch (DeleteFailedException $exception) {
            throw new I18NRestException(
                403,
                $exception->getI18NExceptionMessage()
            );
        }

        $this->event_manager->processEvent('send_notifications', []);
    }

    private function setHeaders(): void
    {
        Header::allowOptionsPatchDelete();
    }

    private function setCreationHeaders(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * @url OPTIONS {id}/metadata
     */
    public function optionsMetadata(int $id): void
    {
        $this->setMetadataHeaders();
    }

    private function setMetadataHeaders()
    {
        Header::allowOptionsPut();
    }

    /**
     * Update the folder metadata and apply this changes to its children
     *
     * <pre>
     * recursion possible options are<br>
     * Possible values:<br>
     *  * none: changes only concerns folder<br>
     *  * folders: changes will apply only on children of type folder<br>
     *  * all_items: changes will apply for every single children regardless of its type<br>
     * </pre>
     *
     *
     * @url    PUT {id}/metadata
     * @access hybrid
     *
     * @param int                       $id             Id of the folder
     * @param PUTMetadataFolderRepresentation $representation {@from body}
     *
     * @status 200
     * @throws I18NRestException 400
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws RestException 404
     */
    public function putMetadata(
        int $id,
        PUTMetadataFolderRepresentation $representation,
    ): void {
        $this->checkAccess();
        $this->setMetadataHeaders();

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

        $this->getDocmanFolderPermissionChecker($project)
            ->checkUserCanWriteFolder($current_user, $id);

        $this->addAllEvent($project);

        $updator = MetadataUpdatorBuilder::build($project, $this->event_manager);
        $updator->updateFolderMetadata(
            $representation,
            $item,
            $project,
            $current_user
        );
    }

    /**
     * @url OPTIONS {id}/permissions
     */
    public function optionsPermissions(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Update permissions of a folder
     *
     * @url    PUT {id}/permissions
     * @access hybrid
     *
     * @param int $id Id of the folder
     * @param DocmanFolderPermissionsForGroupsPUTRepresentation $representation {@from body}
     *
     * @status 200
     *
     * @throws RestException 400
     */
    public function putPermissions(int $id, DocmanFolderPermissionsForGroupsPUTRepresentation $representation): void
    {
        $this->checkAccess();
        $this->optionsPermissions($id);

        $item_request = $this->request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $item         = $item_request->getItem();
        $user         = $item_request->getUser();

        $item->accept($this->getValidator($project, $user, $item), []);
        assert($item instanceof Docman_Folder);

        $this->addAllEvent($project);

        $docman_permission_manager     = $this->getPermissionManager($project);
        $ugroup_manager                = new UGroupManager();
        $permissions_rest_item_updater = new PermissionItemUpdaterFromRESTContext(
            new PermissionItemUpdater(
                new NullResponseFeedbackWrapper(),
                Docman_ItemFactory::instance($project->getID()),
                $docman_permission_manager,
                PermissionsManager::instance(),
                $this->event_manager
            ),
            $docman_permission_manager,
            new DocmanItemPermissionsForGroupsSetFactory(
                $ugroup_manager,
                new UserGroupRetriever($ugroup_manager),
                ProjectManager::instance()
            )
        );
        $permissions_rest_item_updater->updateFolderPermissions($item, $user, $representation);
    }

    /**
     * @throws I18NRestException
     *
     * @psalm-assert \Docman_Folder $item
     */
    private function checkItemCanHaveSubitems(Docman_Item $item): void
    {
        $item_checker = new ItemCanHaveSubItemsChecker();
        $item_checker->checkItemCanHaveSubitems($item);
    }

    private function getPermissionManager(Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function getDocmanFolderPermissionChecker(Project $project): DocmanFolderPermissionChecker
    {
        return new DocmanFolderPermissionChecker($this->getPermissionManager($project));
    }

    private function getValidator(Project $project, \PFUser $current_user, \Docman_Item $item): DocumentBeforeModificationValidatorVisitor
    {
        return new DocumentBeforeModificationValidatorVisitor(
            $this->getPermissionManager($project),
            $current_user,
            $item,
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
        );
    }

    /**
     * @psalm-param class-string<Docman_Item> $expected_item_class_to_copy
     */
    private function getItemCopier(Project $project, string $expected_item_class_to_copy): DocmanItemCopier
    {
        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        assert($docman_plugin instanceof DocmanPlugin);
        $docman_plugin_info = $docman_plugin->getPluginInfo();
        $item_factory       = new Docman_ItemFactory();
        return new DocmanItemCopier(
            $item_factory,
            new BeforeCopyVisitor(
                new DoesItemHasExpectedTypeVisitor($expected_item_class_to_copy),
                $item_factory,
                new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO())
            ),
            $this->getPermissionManager($project),
            new MetadataFactoryBuilder(),
            EventManager::instance(),
            ProjectManager::instance(),
            new Docman_LinkVersionFactory(),
            $docman_plugin_info->getPropertyValueForName('docman_root')
        );
    }

    private function addAllEvent(\Project $project): void
    {
        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);
    }
}
