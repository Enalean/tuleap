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

namespace Tuleap\PullRequest\Reviewer;

use Tuleap\DB\DataAccessObject;

class ReviewerDAO extends DataAccessObject
{
    public function searchReviewers(int $pull_request_id): array
    {
        return $this->getDB()->run(
            'SELECT user.*
                       FROM plugin_pullrequest_reviewer_user
                       JOIN user ON (user.user_id = plugin_pullrequest_reviewer_user.user_id)
                       WHERE plugin_pullrequest_reviewer_user.pull_request_id = ?',
            $pull_request_id
        );
    }
}
