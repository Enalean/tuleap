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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use DateTimeImmutable;
use Docman_EmbeddedFile;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use DocmanPlugin;
use EventManager;
use Luracast\Restler\RestException;
use PluginManager;
use Project;
use ProjectManager;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\Metadata\MetadataFactoryBuilder;
use Tuleap\Docman\REST\v1\CopyItem\BeforeCopyVisitor;
use Tuleap\Docman\REST\v1\CopyItem\DocmanItemCopier;
use Tuleap\Docman\REST\v1\CopyItem\DocmanValidateRepresentationForCopy;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanPOSTFilesRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanEmptyPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanItemCreatorBuilder;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
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
     * @throws I18NRestException 400
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
                new DateTimeImmutable(),
                $files_representation->file_properties
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
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

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        $docman_item_creator = DocmanItemCreatorBuilder::build($project);

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        try {
            if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($folder_representation)) {
                return $docman_item_creator->createFolder(
                    $parent,
                    $current_user,
                    $folder_representation,
                    new DateTimeImmutable(),
                    $project
                );
            }
            if ($representation_for_copy_validation->isValidAsACopyRepresentation($folder_representation)) {
                $docman_plugin = PluginManager::instance()->getPluginByName('docman');
                assert($docman_plugin instanceof DocmanPlugin);
                $docman_plugin_info  = $docman_plugin->getPluginInfo();
                $item_factory        = new Docman_ItemFactory();
                $docman_item_copier  = new DocmanItemCopier(
                    $item_factory,
                    new BeforeCopyVisitor(
                        Docman_Folder::class,
                        $item_factory,
                        new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO())
                    ),
                    $this->getPermissionManager($project),
                    new MetadataFactoryBuilder(),
                    EventManager::instance(),
                    $docman_plugin_info->getPropertyValueForName('docman_root')
                );
                return $docman_item_copier->copyItem(
                    new DateTimeImmutable(),
                    $parent,
                    $current_user,
                    $folder_representation->copy
                );
            }
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
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
                new DateTimeImmutable(),
                $project
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
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
                new DateTimeImmutable(),
                $project
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
            );
        }
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

        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        assert($docman_plugin instanceof DocmanPlugin);
        $docman_plugin_info   = $docman_plugin->getPluginInfo();
        $are_embedded_allowed = $docman_plugin_info->getPropertyValueForName('embedded_are_allowed');

        if ($are_embedded_allowed === false) {
            throw new RestException(403, 'Embedded files are not allowed');
        }

        $representation_for_copy_validation = new DocmanValidateRepresentationForCopy();

        try {
            if ($representation_for_copy_validation->isValidAsANonCopyRepresentation($embeds_representation)) {
                $docman_item_creator = DocmanItemCreatorBuilder::build($project);
                return $docman_item_creator->createEmbedded(
                    $parent,
                    $current_user,
                    $embeds_representation,
                    new DateTimeImmutable(),
                    $project
                );
            }
            if ($representation_for_copy_validation->isValidAsACopyRepresentation($embeds_representation)) {
                $item_factory        = new Docman_ItemFactory();
                $docman_item_copier  = new DocmanItemCopier(
                    $item_factory,
                    new BeforeCopyVisitor(
                        Docman_EmbeddedFile::class,
                        $item_factory,
                        new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO())
                    ),
                    $this->getPermissionManager($project),
                    new MetadataFactoryBuilder(),
                    EventManager::instance(),
                    $docman_plugin_info->getPropertyValueForName('docman_root')
                );
                return $docman_item_copier->copyItem(
                    new DateTimeImmutable(),
                    $parent,
                    $current_user,
                    $embeds_representation->copy
                );
            }
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
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
     * @throws I18NRestException 400
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
                new DateTimeImmutable(),
                $project
            );
        } catch (Metadata\HardCodedMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
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
        $validator_visitor   =$this->getValidator($project, $current_user, $item_to_delete);

        $item_to_delete->accept($validator_visitor);

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        if ($item_to_delete->getParentId() === 0) {
            throw new i18NRestException(400, dgettext("tuleap-docman", "You cannot delete the root folder."));
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

    private function setHeaders()
    {
        Header::allowOptionsPostDelete();
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
        return new DocumentBeforeModificationValidatorVisitor($this->getPermissionManager($project), $current_user, $item, Docman_Folder::class);
    }
}
