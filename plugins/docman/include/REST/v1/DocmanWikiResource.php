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

use Docman_PermissionsManager;
use Docman_SettingsBo;
use Docman_VersionFactory;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataUsageChecker;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPATCHRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiUpdator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager as RestUserManager;

class DocmanWikiResource extends AuthenticatedResource
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
    public function optionsDocumentItems($id): void
    {
        $this->getAllowOptionsPatch();
    }

    /**
     * Create a new version of an existing wiki document
     *
     * <pre>
     * /!\ This route is under construction and will be subject to changes
     * </pre>
     *
     * <br>
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                           $id             Id of the item
     * @param DocmanWikiPATCHRepresentation $representation {@from body}
     *
     * @status 200
     * @throws 400
     * @throws 403
     */

    public function patch(int $id, DocmanWikiPATCHRepresentation $representation): void
    {
        $this->checkAccess();
        $this->getAllowOptionsPatch();

        $item_request = $this->request_builder->buildFromItemId($id);

        /** @var \Docman_Wiki $item */
        $item = $item_request->getItem();

        $validator = new DocumentBeforeModificationValidatorVisitor(\Docman_Wiki::class);
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

        $updator = $this->getDocmanWikiUpdator($docman_item_updator, $project);

        if ($docman_approval_table_retriever->hasApprovalTable($item)) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'It is not possible to update a wiki page with approval table.')
            );
        }

        try {
            $updator->updateWiki(
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
        } catch (Metadata\StatusNotFoundException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (Metadata\ItemStatusUsageMismatchException $e) {
            throw new RestException(400, 'The "Status" property is not activated for this item.');
        } catch (Metadata\InvalidDateComparisonException $e) {
            throw new RestException(400, 'The obsolescence date is before the current date');
        } catch (Metadata\InvalidDateTimeFormatException $e) {
            throw new RestException(400, 'The date format is incorrect. The format should be YYYY-MM-DD');
        } catch (Metadata\ObsoloscenceDateUsageMismatchException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    private function getDocmanItemsEventAdder(): DocmanItemsEventAdder
    {
        return new DocmanItemsEventAdder($this->event_manager);
    }

    private function getAllowOptionsPatch(): void
    {
        Header::allowOptionsPatch();
    }

    private function getDocmanWikiUpdator(DocmanItemUpdator $docman_item_updator, Project $project): DocmanWikiUpdator
    {

        $docman_setting_bo                            = new Docman_SettingsBo($project->getGroupId());
        $hardcoded_metadata_status_checker            = new HardcodedMetadataUsageChecker($docman_setting_bo);
        $hardcoded_metadata_obsolescence_date_checker = new HardcodedMetdataObsolescenceDateChecker(
            $docman_setting_bo
        );
        return new DocmanWikiUpdator(
            new \Docman_VersionFactory(),
            new LockChecker(new \Docman_LockFactory()),
            new \Docman_ItemFactory(),
            $this->event_manager,
            $docman_item_updator,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new ItemStatusMapper(),
            $hardcoded_metadata_status_checker,
            $hardcoded_metadata_obsolescence_date_checker,
            new HardcodedMetadataObsolescenceDateRetriever(
                $hardcoded_metadata_obsolescence_date_checker
            )
        );
    }
}
