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

use Docman_FileStorage;
use Docman_LockFactory;
use Docman_VersionFactory;
use Luracast\Restler\RestException;
use PluginManager;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\Exceptions\ItemHasApprovalTableButNoApprovalActionException;
use Tuleap\Docman\ApprovalTable\Exceptions\ItemHasNoApprovalTableButHasApprovalActionException;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFilesPATCHRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedFileUpdator;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedFileVersionCreationBeforeUpdateValidator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanEmbeddedFilesResource extends AuthenticatedResource
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var RestUserManager
     */
    private $rest_user_manager;
    /**
     * @var DocmanItemsRequestBuilder
     */
    private $request_builder;

    public function __construct()
    {
        $this->rest_user_manager = RestUserManager::build();
        $this->project_manager   = ProjectManager::instance();
        $this->request_builder   = new DocmanItemsRequestBuilder($this->rest_user_manager, $this->project_manager);
        $this->event_manager     = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsDocumentItems($id)
    {
        $this->getAllowOptionsPatch();
    }

    /**
     * Create a new version of an existing embedded file document
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * <br>
     * <pre>
     * approval_table_action should be provided only if item has an existing approval table.<br>
     * Possible values:<br>
     *  * copy: Creates an approval table based on the previous one<br>
     *  * reset: Reset the current approval table<br>
     *  * empty: No approbation needed for the new version of this document<br>
     * </pre>
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                                    $id             Id of the item
     * @param DocmanEmbeddedFilesPATCHRepresentation $representation {@from body}
     *
     * @status 200
     * @throws 400
     * @throws 403
     * @throws 501
     */

    public function patch(int $id, DocmanEmbeddedFilesPATCHRepresentation $representation)
    {
        $this->checkAccess();
        $this->getAllowOptionsPatch();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();

        $validator = new EmbeddedFileVersionCreationBeforeUpdateValidator();
        $item->accept($validator, []);
        /** @var \Docman_EmbeddedFile $item */

        $current_user = $this->rest_user_manager->getCurrentUser();

        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
             ->checkUserCanWriteFolder($current_user, (int)$item->getParentId());

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        $docman_approval_table_retriever = new ApprovalTableRetriever(new \Docman_ApprovalTableFactoriesFactory());

        $builder             = new DocmanItemUpdatorBuilder();
        $docman_item_updator = $builder->build($this->event_manager);

        $docman_plugin       = PluginManager::instance()->getPluginByName('docman');
        $docman_root         = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');
        $version_factory     = new Docman_VersionFactory();
        $docman_item_updator = new DocmanEmbeddedFileUpdator(
            new Docman_FileStorage($docman_root),
            $version_factory,
            new LockChecker(new Docman_LockFactory()),
            $docman_item_updator,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );

        try {
            $approval_check = new ApprovalTableUpdateActionChecker($docman_approval_table_retriever);
            $approval_check->checkApprovalTableForItem($representation->approval_table_action, $item);
            $docman_item_updator->updateEmbeddedFile(
                $item,
                $current_user,
                $representation
            );
        } catch (ExceptionItemIsLockedByAnotherUser $exception) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'Document is locked by another user.')
            );
        } catch (ItemHasApprovalTableButNoApprovalActionException $exception) {
            throw new I18NRestException(
                400,
                $exception->getMessage()
            );
        } catch (ItemHasNoApprovalTableButHasApprovalActionException $exception) {
            throw new I18NRestException(
                400,
                $exception->getMessage()
            );
        }
    }

    private function getDocmanFolderPermissionChecker(Project $project): DocmanFolderPermissionChecker
    {
        return new DocmanFolderPermissionChecker(\Docman_PermissionsManager::instance($project->getGroupId()));
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function getAllowOptionsPatch(): void
    {
        Header::allowOptionsPatch();
    }
}
