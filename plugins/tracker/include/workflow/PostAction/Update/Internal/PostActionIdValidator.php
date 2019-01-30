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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\Update\PostAction;

class PostActionIdValidator
{
    /**
     * @throws DuplicatePostActionException
     */
    public function validate(PostAction ...$post_actions): void
    {
        $ids = $this->extractPostActionIds(...$post_actions);
        if ($this->hasDuplicateIds(...$ids)) {
            throw new DuplicatePostActionException();
        }
    }

    /**
     * @return int[]
     */
    private function extractPostActionIds(PostAction ...$actions): array
    {
        $ids = array_map(
            function (PostAction $action) {
                return $action->getId();
            },
            $actions
        );
        return array_filter(
            $ids,
            function ($id) {
                return $id !== null;
            }
        );
    }

    private function hasDuplicateIds(int ...$ids): bool
    {
        return count($ids) !== count(array_unique($ids));
    }
}
