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

use Docman_LockFactory;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\Exceptions\ItemHasApprovalTableButNoApprovalActionException;
use Tuleap\Docman\ApprovalTable\Exceptions\ItemHasNoApprovalTableButHasApprovalActionException;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\Files\CreatedItemFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanFilesPATCHRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanItemFileUpdator;
use Tuleap\Docman\REST\v1\Files\FileVersionToUploadVisitorBeforeUpdateValidator;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanFilesResource extends AuthenticatedResource
{
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
        $this->request_builder   = new DocmanItemsRequestBuilder($this->rest_user_manager, ProjectManager::instance());
        $this->event_manager = \EventManager::instance();
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsDocumentItems($id)
    {
        $this->getAllowOptionsPatch();
    }

    /**
     * Patch an element of document manager
     *
     * Create a new version of an existing file document
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
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
     * @param int                            $id             Id of the item
     * @param DocmanFilesPATCHRepresentation $representation {@from body}
     *
     * @return CreatedItemFilePropertiesRepresentation
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 501
     */

    public function patch(int $id, DocmanFilesPATCHRepresentation $representation)
    {
        $this->checkAccess();
        $this->getAllowOptionsPatch();

        $item_request = $this->request_builder->buildFromItemId($id);
        $item         = $item_request->getItem();

        $validator = new FileVersionToUploadVisitorBeforeUpdateValidator();
        $item->accept($validator, []);

        $current_user = $this->rest_user_manager->getCurrentUser();

        $project = $item_request->getProject();
        $this->getDocmanFolderPermissionChecker($project)
             ->checkUserCanWriteFolder($current_user, (int)$item->getParentId());

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);


        $docman_approval_table_retriever = new ApprovalTableRetriever(new \Docman_ApprovalTableFactoriesFactory());
        $docman_item_updator             = new DocmanItemFileUpdator(
            $docman_approval_table_retriever,
            new Docman_LockFactory(),
            new VersionToUploadCreator(
                new DocumentOnGoingVersionToUploadDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new LockChecker(new Docman_LockFactory())
        );

        try {
            $approval_check = new ApprovalTableUpdateActionChecker($docman_approval_table_retriever);
            $approval_check->checkApprovalTableForItem($representation->approval_table_action, $item);
            return $docman_item_updator->updateFile(
                $item,
                $current_user,
                $representation,
                new \DateTimeImmutable()
            );
        } catch (ExceptionItemIsLockedByAnotherUser $exception) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'Document is locked by another user.')
            );
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        } catch (ItemHasApprovalTableButNoApprovalActionException $exception) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-docman',
                        '%s has an approval table, you must provide an option to have approval table on new version creation.'
                    ),
                    $item->title
                )
            );
        } catch (ItemHasNoApprovalTableButHasApprovalActionException $exception) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'Impossible to update a file which already has an approval table without approval action.'
                )
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
