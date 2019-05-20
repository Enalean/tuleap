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

namespace Tuleap\Docman\REST\v1\Wiki;

use Docman_ItemFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataUsageChecker;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

class DocmanWikiUpdator
{
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var LockChecker
     */
    private $lock_checker;
    /**
     * @var \Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var DocmanItemUpdator
     */
    private $updator;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var HardcodedMetadataUsageChecker
     */
    private $metadata_usage_checker;
    /**
     * @var HardcodedMetdataObsolescenceDateChecker
     */
    private $obsolescence_date_checker;
    /**
     * @var ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;

    public function __construct(
        \Docman_VersionFactory $version_factory,
        LockChecker $lock_checker,
        Docman_ItemFactory $docman_item_factory,
        \EventManager $event_manager,
        DocmanItemUpdator $updator,
        DBTransactionExecutor $transaction_executor,
        ItemStatusMapper $status_mapper,
        HardcodedMetadataUsageChecker $metadata_usage_checker,
        HardcodedMetdataObsolescenceDateChecker $obsolescence_date_checker,
        HardcodedMetadataObsolescenceDateRetriever $date_retriever
    ) {
        $this->version_factory           = $version_factory;
        $this->lock_checker              = $lock_checker;
        $this->docman_item_factory       = $docman_item_factory;
        $this->event_manager             = $event_manager;
        $this->updator                   = $updator;
        $this->transaction_executor      = $transaction_executor;
        $this->metadata_usage_checker    = $metadata_usage_checker;
        $this->obsolescence_date_checker = $obsolescence_date_checker;
        $this->status_mapper             = $status_mapper;
        $this->date_retriever            = $date_retriever;
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateComparisonException
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateTimeFormatException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ItemStatusUsageMismatchException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateDisabledException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateMissingParameterException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateNullException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundBadStatusGivenException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundNullException
     */
    public function updateWiki(
        \Docman_Wiki $item,
        \PFUser $current_user,
        DocmanWikiPATCHRepresentation $representation,
        \DateTimeImmutable $current_time
    ): void {
        $this->lock_checker->checkItemIsLocked($item, $current_user);

        $status_id = $this->getStatusId($representation);

        $obsolescence_date_time_stamp = $this->getObsolescenceDateTimestamp($representation, $current_time);

        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $status_id, $obsolescence_date_time_stamp) {
                $next_version_id = (int)$this->version_factory->getNextVersionNumber($item);

                $new_wiki_version_row = [
                    'id'                => $item->getId(),
                    'user_id'           => $current_user->getId(),
                    'wiki_page'         => $representation->wiki_properties->page_name,
                    'title'             => $representation->title,
                    'description'       => $representation->description,
                    'status'            => $status_id,
                    'obsolescence_date' => $obsolescence_date_time_stamp
                ];


                $this->docman_item_factory->update($new_wiki_version_row);

                $documents = $this->docman_item_factory->getWikiPageReferencers($item->getPagename(), $item->getGroupId());
                foreach ($documents as $document) {
                    $this->event_manager->processEvent(
                        'plugin_docman_event_wikipage_update',
                        [
                            'group_id'  => $item->getGroupId(),
                            'item'      => $document,
                            'user'      => $current_user,
                            'wiki_page' => $representation->wiki_properties->page_name,
                            'old_value' => $next_version_id - 1,
                            'new_value' => $next_version_id
                        ]
                    );
                }

                $last_version = $this->version_factory->getCurrentVersionForItem($item);
                $this->event_manager->processEvent(
                    'plugin_docman_event_edit',
                    [
                        'group_id' => $item->getGroupId(),
                        'item'     => $item,
                        'user'     => $current_user
                    ]
                );

                $this->updator->updateCommonDataWithoutApprovalTable(
                    $item,
                    $representation->should_lock_file,
                    $current_user,
                    $last_version
                );
            }
        );
    }

    /**
     * @throws \Tuleap\Docman\REST\v1\Metadata\ItemStatusUsageMismatchException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundBadStatusGivenException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundNullException
     */
    private function getStatusId(DocmanWikiPATCHRepresentation $representation): int
    {
        $this->metadata_usage_checker->checkStatusIsNotSetWhenStatusMetadataIsNotAllowed(
            $representation->status
        );
        $status_id = $this->status_mapper->getItemStatusIdFromItemStatusString(
            $representation->status
        );
        return $status_id;
    }

    /**
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateComparisonException
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateTimeFormatException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateDisabledException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateMissingParameterException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateNullException
     */
    private function getObsolescenceDateTimestamp(
        DocmanWikiPATCHRepresentation $representation,
        \DateTimeImmutable $current_time
    ): int {
        $this->obsolescence_date_checker->checkObsolescenceDateUsage(
            $representation->obsolescence_date,
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );
        $obsolescence_date_time_stamp = $this->date_retriever->getTimeStampOfDate(
            $representation->obsolescence_date,
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );
        $this->obsolescence_date_checker->checkDateValidity(
            $current_time->getTimestamp(),
            $obsolescence_date_time_stamp,
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI
        );
        return $obsolescence_date_time_stamp;
    }
}
