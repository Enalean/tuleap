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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Fault;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Comment\CommentNotFoundFault;
use Tuleap\PullRequest\Criterion\MalformedQueryFault;
use Tuleap\PullRequest\InlineComment\InlineCommentNotFoundFault;
use Tuleap\PullRequest\PullRequestNotFoundFault;

final class FaultMapper
{
    /**
     * @throws RestException
     */
    public static function mapToRestException(Fault $fault): never
    {
        $status_code = match ($fault::class) {
            MalformedQueryFault::class => 400,
            CommentIsNotFromCurrentUserFault::class, CommentFormatNotAllowedFault::class => 403,
            CommentNotFoundFault::class, CannotAccessToPullRequestFault::class, PullRequestNotFoundFault::class, InlineCommentNotFoundFault::class, GitRepositoryNotFoundFault::class => 404,
            default => 500,
        };
        throw new RestException($status_code, (string) $fault);
    }
}
