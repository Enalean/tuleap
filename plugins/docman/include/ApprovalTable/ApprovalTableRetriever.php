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

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTable;
use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableVersionnedFactory;
use Docman_Item;

class ApprovalTableRetriever
{
    /**
     * @var Docman_ApprovalTableFactoriesFactory
     */
    private $approval_table_factory;
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;

    public function __construct(
        \Docman_ApprovalTableFactoriesFactory $approval_table_factory,
        \Docman_VersionFactory $version_factory
    ) {
        $this->approval_table_factory = $approval_table_factory;
        $this->version_factory        = $version_factory;
    }

    public function retrieveByItem(Docman_Item $item): ?Docman_ApprovalTable
    {
        $approval_table = $this->getLastApprovalTable($item);
        if (! $approval_table || $approval_table->isDisabled()) {
            return null;
        }

        return $approval_table;
    }

    public function hasApprovalTable(Docman_Item $item): bool
    {
        $approval_table = $this->getLastApprovalTable($item);
        return $approval_table !== null;
    }

    private function getLastApprovalTable(Docman_Item $item): ?Docman_ApprovalTable
    {
        $version    = $this->version_factory->getCurrentVersionForItem($item);
        $version_id = null;
        if ($version) {
            $version_id = $version->getNumber();
        }

        $item_factory = $this->approval_table_factory->getFromItem($item, $version_id);

        if ($item_factory instanceof Docman_ApprovalTableVersionnedFactory) {
            return $item_factory->getLastTableForItem();
        }

        $table_factory = $this->approval_table_factory->getSpecificFactoryFromItem($item);
        if (! $table_factory) {
            return null;
        }

        return $table_factory->getTable();
    }
}
