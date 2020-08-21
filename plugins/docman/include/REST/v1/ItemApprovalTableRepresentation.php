<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
class ItemApprovalTableRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var MinimalUserRepresentation
     */
    public $table_owner;
    /**
     * @var string
     */
    public $approval_state;
    /**
     * @var string
     */
    public $approval_request_date;

    /**
     * @var bool
     */
    public $has_been_approved;

    public function __construct(
        \Docman_ApprovalTable $approval_table,
        MinimalUserRepresentation $table_owner,
        ApprovalTableStateMapper $status_mapper
    ) {
        $this->id                    = JsonCast::toInt($approval_table->getId());
        $this->table_owner           = $table_owner;
        $this->approval_state        = $status_mapper->getStatusStringFromStatusId((int) $approval_table->getApprovalState());
        $this->approval_request_date = JsonCast::toDate($approval_table->getDate());
        $this->has_been_approved     = JsonCast::toBoolean(
            $approval_table->getApprovalState() === PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED
        );
    }
}
