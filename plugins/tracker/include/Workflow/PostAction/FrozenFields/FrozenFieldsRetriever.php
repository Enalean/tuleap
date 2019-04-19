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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

class FrozenFieldsRetriever
{
    /** @var FrozenFieldsDao */
    private $frozen_dao;

    public function __construct(FrozenFieldsDao $frozen_dao)
    {
        $this->frozen_dao = $frozen_dao;
    }

    /**
     * @throws NoFrozenFieldsPostActionException
     */
    public function getFrozenFields(\Transition $transition): FrozenFields
    {
        $rows = $this->frozen_dao->searchByTransitionId((int) $transition->getId());

        $field_ids = [];
        $post_action_id = null;
        foreach ($rows as $row) {
            $field_ids[] = $row['field_id'];
            // There is only one FrozenFields post-action per transition, so we just choose the last row's id
            $post_action_id = $row['postaction_id'];
        }
        if ($post_action_id === null) {
            throw new NoFrozenFieldsPostActionException();
        }
        return new FrozenFields($transition, $post_action_id, $field_ids);
    }
}
