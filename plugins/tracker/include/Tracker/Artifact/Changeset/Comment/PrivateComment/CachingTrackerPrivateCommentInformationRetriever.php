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

final class CachingTrackerPrivateCommentInformationRetriever implements RetrieveTrackerPrivateCommentInformation
{
    /**
     * @var RetrieveTrackerPrivateCommentInformation
     */
    private $tracker_private_comment_information_retriever;
    /**
     * @var array<int,bool>
     */
    private $local_cache = [];

    public function __construct(RetrieveTrackerPrivateCommentInformation $tracker_private_comment_information_retriever)
    {
        $this->tracker_private_comment_information_retriever = $tracker_private_comment_information_retriever;
    }

    public function doesTrackerAllowPrivateComments(Tracker $tracker): bool
    {
        $tracker_id = $tracker->getId();
        if (isset($this->local_cache[$tracker_id])) {
            return $this->local_cache[$tracker_id];
        }

        $does_tracker_allow_private_comments = $this->tracker_private_comment_information_retriever->doesTrackerAllowPrivateComments($tracker);
        $this->local_cache[$tracker_id]      = $does_tracker_allow_private_comments;

        return $does_tracker_allow_private_comments;
    }
}
