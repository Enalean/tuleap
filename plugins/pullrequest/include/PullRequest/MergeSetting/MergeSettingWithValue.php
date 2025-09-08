<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\MergeSetting;

final class MergeSettingWithValue implements MergeSetting
{
    private $is_merge_commit_allowed;

    public function __construct($is_merge_commit_allowed)
    {
        $this->is_merge_commit_allowed = $is_merge_commit_allowed > 0;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isMergeCommitAllowed()
    {
        return $this->is_merge_commit_allowed;
    }
}
