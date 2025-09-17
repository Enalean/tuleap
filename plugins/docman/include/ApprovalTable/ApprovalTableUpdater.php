<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable;

class ApprovalTableUpdater
{
    public const string APPROVAL_TABLE_UPDATE_COPY  = 'copy';
    public const string APPROVAL_TABLE_UPDATE_RESET = 'reset';
    public const string APPROVAL_TABLE_UPDATE_EMPTY = 'empty';

    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;
    /**
     * @var \Docman_ApprovalTableFactoriesFactory
     */
    private $approval_table_factories_factory;

    public function __construct(ApprovalTableRetriever $approval_table_retriever, \Docman_ApprovalTableFactoriesFactory $approval_table_factories_factory)
    {
        $this->approval_table_retriever         = $approval_table_retriever;
        $this->approval_table_factories_factory = $approval_table_factories_factory;
    }

    public function updateApprovalTable(\Docman_Item $item, \PFUser $user, string $approval_table_action): void
    {
        if (! $this->approval_table_retriever->hasApprovalTable($item)) {
            return;
        }
        $approval_file = $this->approval_table_factories_factory->getSpecificFactoryFromItem($item);
        $approval_file->createTable($user->getId(), $approval_table_action);
    }
}
