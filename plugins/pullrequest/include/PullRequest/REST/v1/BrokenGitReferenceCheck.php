<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\PullRequest\REST\v1;

use Tuleap\Option\Option;
use Tuleap\PullRequest\GitReference\BypassBrokenGitReferenceCheck;
use Tuleap\PullRequest\REST\v1\PullRequestPATCHRepresentation;
use Tuleap\PullRequest\REST\v1\PullRequestRepresentation;

final class BrokenGitReferenceCheck
{
    /**
     * @return Option<BypassBrokenGitReferenceCheck>
     */
    public static function fromPATCHRepresentation(PullRequestPATCHRepresentation $body): Option
    {
        if ($body->title || $body->description || $body->description_format) {
            return BypassBrokenGitReferenceCheck::check();
        }

        return $body->status === PullRequestRepresentation::STATUS_ABANDON ?
            BypassBrokenGitReferenceCheck::skip() :
            BypassBrokenGitReferenceCheck::check();
    }
}
