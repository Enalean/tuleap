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

namespace Tuleap\Docman\REST\v1\Links;

use Docman_ItemFactory;
use Docman_VersionFactory;
use EventManager;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

class DocmanLinkUpdator
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
     * @var DocmanItemUpdator
     */
    private $updator;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var DocmanLinksValidityChecker
     */
    private $links_validity_checker;
    /**
     * @var \Docman_LinkVersionFactory
     */
    private $docman_link_version_factory;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;

    public function __construct(
        Docman_VersionFactory $version_factory,
        DocmanItemUpdator $updator,
        LockChecker $lock_checker,
        Docman_ItemFactory $item_factory,
        EventManager $event_manager,
        DocmanLinksValidityChecker $links_validity_checker,
        \Docman_LinkVersionFactory $docman_link_version_factory,
        DBTransactionExecutor $transaction_executor,
        ItemStatusMapper $status_mapper,
        HardcodedMetadataObsolescenceDateRetriever $date_retriever
    ) {
        $this->version_factory             = $version_factory;
        $this->updator                     = $updator;
        $this->lock_checker                = $lock_checker;
        $this->item_factory                = $item_factory;
        $this->event_manager               = $event_manager;
        $this->links_validity_checker      = $links_validity_checker;
        $this->docman_link_version_factory = $docman_link_version_factory;
        $this->transaction_executor        = $transaction_executor;
        $this->status_mapper               = $status_mapper;
        $this->date_retriever              = $date_retriever;
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     * @throws \Luracast\Restler\RestException
     * @throws \Throwable
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateComparisonException
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateTimeFormatException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ItemStatusUsageMismatchException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateDisabledException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundBadStatusGivenException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundNullException
     */
    public function updateLink(
        \Docman_Link $item,
        \PFUser $current_user,
        DocmanLinkPATCHRepresentation $representation,
        \DateTimeImmutable $current_time
    ): void {
        $this->lock_checker->checkItemIsLocked($item, $current_user);

        $this->links_validity_checker->checkLinkValidity($representation->link_properties->link_url);

        $status_id = $this->status_mapper->getItemStatusIdFromItemStatusString(
            $representation->status
        );

        $obsolescence_date_time_stamp = $this->date_retriever->getTimeStampOfDate(
            $representation->obsolescence_date,
            $current_time
        );

        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $status_id, $obsolescence_date_time_stamp) {
                $next_version_id = (int)$this->version_factory->getNextVersionNumber($item);

                $date = new \DateTimeImmutable();

                $new_link_version_row = [
                    'item_id'           => $item->getId(),
                    'number'            => $next_version_id,
                    'user_id'           => $current_user->getId(),
                    'label'             => $representation->version_title,
                    'changelog'         => $representation->change_log,
                    'date'              => $date->getTimestamp(),
                    'link_url'          => $representation->link_properties->link_url,
                    'title'             => $representation->title,
                    'description'       => $representation->description,
                    'status'            => $status_id,
                    'obsolescence_date' => $obsolescence_date_time_stamp
                ];

                $this->item_factory->updateLinkWithMetadata($item, $new_link_version_row);
            }
        );

        $version = $this->docman_link_version_factory->getLatestVersion($item);

        $this->updator->updateCommonData(
            $item,
            $representation->should_lock_file,
            $current_user,
            $representation->approval_table_action,
            $version
        );

        $last_version = $this->version_factory->getCurrentVersionForItem($item);

        $event_data = [
            'item'    => $item,
            'version' => $last_version,
        ];
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_LINKVERSION, $event_data);
    }
}
