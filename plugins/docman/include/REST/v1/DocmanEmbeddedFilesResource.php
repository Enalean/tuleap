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
use Docman_PermissionsManager;
use Docman_SettingsBo;
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
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataUsageChecker;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
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
        $this->setHeaders();
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
        $this->setHeaders();

        $item_request = $this->request_builder->buildFromItemId($id);

        /** @var \Docman_EmbeddedFile $item */
        $item = $item_request->getItem();

        $validator = new DocumentBeforeModificationValidatorVisitor(\Docman_EmbeddedFile::class);
        $item->accept($validator, []);

        $current_user = $this->rest_user_manager->getCurrentUser();

        $project = $item_request->getProject();

        $docman_permission_manager = Docman_PermissionsManager::instance($project->getGroupId());
        if (! $docman_permission_manager->userCanWrite($current_user, $item->getId())) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'You are not allowed to write this item.')
            );
        }

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        $docman_approval_table_retriever = new ApprovalTableRetriever(
            new \Docman_ApprovalTableFactoriesFactory(),
            new Docman_VersionFactory()
        );

        $builder             = new DocmanItemUpdatorBuilder();
        $docman_item_updator = $builder->build($this->event_manager);

        $docman_plugin = PluginManager::instance()->getPluginByName('docman');
        $docman_root   = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');
        $updator       = $this->getEmbeddedFileUpdator($docman_root, $docman_item_updator, $project);

        try {
            $approval_check = new ApprovalTableUpdateActionChecker($docman_approval_table_retriever);
            $approval_check->checkApprovalTableForItem($representation->approval_table_action, $item);
            $updator->updateEmbeddedFile(
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
        } catch (Metadata\ObsolescenceDateMissingParameterException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    '"obsolescence_date" parameter is required to create a new document.'
                )
            );
        } catch (Metadata\ObsolescenceDateNullException $e) {
            throw new I18NRestException(
                400,
                dgettext(
                    'tuleap-docman',
                    'The date cannot be null'
                )
            );
        } catch (Metadata\StatusNotFoundBadStatusGivenException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The status "%s" is invalid.'),
                    (string) $representation->status
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
     * Delete an embedded file document in the document manager
     *
     * Delete an existing embedded file document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param int $id Id of the embedded file
     *
     * @status 200
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function delete(int $id) : void
    {
        $this->checkAccess();
        $this->setHeaders();

        $item_request      = $this->request_builder->buildFromItemId($id);
        $item_to_delete    = $item_request->getItem();
        $current_user      = $this->rest_user_manager->getCurrentUser();
        $project           = $item_request->getProject();
        $validator_visitor = new DocumentBeforeModificationValidatorVisitor(\Docman_EmbeddedFile::class);

        $docman_permission_manager = Docman_PermissionsManager::instance($project->getGroupId());
        if (! $docman_permission_manager->userCanDelete($current_user, $item_to_delete)) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'You are not allowed to delete this item.')
            );
        }

        $item_to_delete->accept($validator_visitor);

        $event_adder = $this->getDocmanItemsEventAdder();
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);

        (new \Docman_ItemFactory())->delete($item_to_delete);

        $this->event_manager->processEvent('send_notifications', []);
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function setHeaders(): void
    {
        Header::allowOptionsPatchDelete();
    }


    private function getEmbeddedFileUpdator(
        string $docman_root,
        DocmanItemUpdator $docman_item_updator,
        Project $project
    ): DocmanEmbeddedFileUpdator {
        $version_factory                              = new Docman_VersionFactory();
        $docman_setting_bo                            = new Docman_SettingsBo($project->getGroupId());
        $hardcoded_metadata_status_checker            = new HardcodedMetadataUsageChecker($docman_setting_bo);
        $hardcoded_metadata_obsolescence_date_checker = new HardcodedMetdataObsolescenceDateChecker(
            $docman_setting_bo
        );

        $docman_item_updator = new DocmanEmbeddedFileUpdator(
            new Docman_FileStorage($docman_root),
            $version_factory,
            new \Docman_ItemFactory(),
            new LockChecker(new Docman_LockFactory()),
            $docman_item_updator,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new ItemStatusMapper(),
            $hardcoded_metadata_status_checker,
            $hardcoded_metadata_obsolescence_date_checker,
            new HardcodedMetadataObsolescenceDateRetriever(
                $hardcoded_metadata_obsolescence_date_checker
            )
        );
        return $docman_item_updator;
    }
}
