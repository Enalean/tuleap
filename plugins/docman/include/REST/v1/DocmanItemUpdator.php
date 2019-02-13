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

use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;

class DocmanItemUpdator
{
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;

    public function __construct(ApprovalTableRetriever $approval_table_retriever)
    {
        $this->approval_table_retriever = $approval_table_retriever;
    }

    /**
     * @throws ExceptionDocumentHasApprovalTable
     */
    public function update(\Docman_Item $item): void
    {
        $approval_table = $this->approval_table_retriever->retrieveByItem($item);
        if ($approval_table) {
            throw new ExceptionDocumentHasApprovalTable();
        }
    }
}
