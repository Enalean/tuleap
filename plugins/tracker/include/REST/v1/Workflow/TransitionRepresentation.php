<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

/**
 * @psalm-immutable
 */
class TransitionRepresentation
{

    /**
     * @var int
     */
    public $id;

    /**
     * @var int Source value id
     */
    public $from_id;

    /**
     * @var int Target value id
     */
    public $to_id;

    /**
     * @var string[] Ids of authorized user groups {@type string}
     */
    public $authorized_user_group_ids;

    /**
     * @var int[] Ids of not empty fields {@type int}
     */
    public $not_empty_field_ids;

    /**
     * @var bool
     */
    public $is_comment_required;

    /**
     * @param int $id
     * @param int $from_id
     * @param int $to_id
     * @param string[] $authorized_user_group_ids
     * @param int[] $not_empty_field_ids
     * @param bool $is_comment_required
     */
    public function __construct(
        $id,
        $from_id,
        $to_id,
        array $authorized_user_group_ids,
        array $not_empty_field_ids,
        $is_comment_required
    ) {
        $this->id = $id;
        $this->from_id = $from_id;
        $this->to_id = $to_id;
        $this->authorized_user_group_ids = $authorized_user_group_ids;
        $this->not_empty_field_ids = $not_empty_field_ids;
        $this->is_comment_required = $is_comment_required;
    }
}
