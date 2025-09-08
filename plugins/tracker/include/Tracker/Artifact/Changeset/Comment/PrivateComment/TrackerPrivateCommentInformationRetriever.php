<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment;

use Tuleap\Tracker\Tracker;

final class TrackerPrivateCommentInformationRetriever implements RetrieveTrackerPrivateCommentInformation
{
    /**
     * @var TrackerPrivateCommentUGroupEnabledDao
     */
    private $tracker_private_comment_ugroup_enabled_dao;

    public function __construct(TrackerPrivateCommentUGroupEnabledDao $tracker_private_comment_ugroup_enabled_dao)
    {
        $this->tracker_private_comment_ugroup_enabled_dao = $tracker_private_comment_ugroup_enabled_dao;
    }

    #[\Override]
    public function doesTrackerAllowPrivateComments(Tracker $tracker): bool
    {
        return $this->tracker_private_comment_ugroup_enabled_dao->isTrackerEnabledPrivateComment($tracker->getId());
    }
}
