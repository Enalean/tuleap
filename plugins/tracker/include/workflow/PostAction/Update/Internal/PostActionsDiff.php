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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\Update\PostAction;

/**
 * Comparison result between two sets of post actions.
 * Deleted post actions are not represented here because they are handled in other way.
 */
class PostActionsDiff
{
    /** @var PostAction[] */
    private $added = [];

    /** @var PostAction[] */
    private $updated = [];

    /**
     * @param PostAction[] $added
     * @param PostAction[] $updated
     */
    public function __construct(
        array $added,
        array $updated
    ) {
        $this->added   = $added;
        $this->updated = $updated;
    }

    /**
     * @return PostAction[]
     */
    public function getAddedActions(): array
    {
        return $this->added;
    }

    /**
     * @return PostAction[]
     */
    public function getUpdatedActions(): array
    {
        return $this->updated;
    }
}
