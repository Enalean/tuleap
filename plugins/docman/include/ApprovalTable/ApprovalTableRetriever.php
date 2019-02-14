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

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTable;
use Docman_ApprovalTableFactoriesFactory;
use Docman_Item;

class ApprovalTableRetriever
{
    /**
     * @var Docman_ApprovalTableFactoriesFactory
     */
    private $approval_table_factory;

    public function __construct(\Docman_ApprovalTableFactoriesFactory $approval_table_factory)
    {
        $this->approval_table_factory = $approval_table_factory;
    }

    public function retrieveByItem(Docman_Item $item): ?Docman_ApprovalTable
    {
        $table_factory = $this->approval_table_factory->getSpecificFactoryFromItem($item);
        if (! $table_factory) {
            return null;
        }

        /* @var $approval_table \Docman_ApprovalTable */
        $approval_table = $table_factory->getTable();
        if (! $approval_table || ! $approval_table->isEnabled()) {
            return null;
        }

        return $approval_table;
    }
}
