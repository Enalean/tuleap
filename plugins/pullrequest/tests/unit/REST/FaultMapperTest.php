<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use GitRepoNotFoundException;
use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Fault;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Comment\CommentNotFoundFault;
use Tuleap\Test\PHPUnit\TestCase;

final class FaultMapperTest extends TestCase
{
    public static function dataProviderFaults(): iterable
    {
        yield 'Cannot access to the Pull Request' => [CannotAccessToPullRequestFault::fromUpdatingComment(new GitRepoNotFoundException('Not Found')), 404];
        yield 'Comment not found' => [CommentNotFoundFault::withCommentId(15), 404];
        yield 'Cannot update other user comment' => [CommentIsNotFromCurrentUserFault::fromComment(), 403];
        yield 'Cannot edit comment which is not in Markdown' => [CommentFormatNotAllowedFault::withGivenFormat("hehe"), 403];
    }

    /**
     * @dataProvider dataProviderFaults
     */
    public function testItMapsFaultsToRestExceptions(Fault $fault, int $expected_status_code): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode($expected_status_code);
        FaultMapper::mapToRestException($fault);
    }
}
