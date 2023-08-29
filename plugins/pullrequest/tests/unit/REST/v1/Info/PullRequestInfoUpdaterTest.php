<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Info;

use Luracast\Restler\RestException;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\REST\v1\Permissions\PullRequestIsMergeableChecker;
use Tuleap\PullRequest\REST\v1\PullRequestPATCHRepresentation;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestInfoUpdaterTest extends TestCase
{
    /**
     * @var Factory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;
    private \Tuleap\PullRequest\PullRequest $pullrequest;
    /**
     * @var PullRequestIsMergeableChecker&\PHPUnit\Framework\MockObject\MockObject
     */
    private $pull_request_is_mergeable_checker;
    private PullRequestInfoUpdater $info_updater;

    protected function setUp(): void
    {
        $this->factory                           = $this->createMock(Factory::class);
        $this->pull_request_is_mergeable_checker = $this->createMock(PullRequestIsMergeableChecker::class);
        $this->pullrequest                       = PullRequestTestBuilder::aMergedPullRequest()->build();

        $this->info_updater = new PullRequestInfoUpdater($this->factory, $this->pull_request_is_mergeable_checker);
    }

    public function testItCheckPermissionsWhenUserIsNotOwnerOfPullRequest(): void
    {
        $user           = UserTestBuilder::anActiveUser()->withId(1234)->build();
        $project_id     = 104;
        $representation = new PullRequestPATCHRepresentation(PullRequest::STATUS_REVIEW, "My PR", "a description", Comment::FORMAT_TEXT);

        $this->pull_request_is_mergeable_checker->expects(self::once())->method("checkUserCanMerge")->willThrowException(new RestException(403, 'Forbidden'));
        $this->factory->expects(self::never())->method("updateTitle");
        $this->factory->expects(self::never())->method("updateDescription");

        $this->expectExceptionCode(403);

        $this->info_updater->patchInfo($user, $this->pullrequest, $project_id, $representation);
    }

    public function testItDoesNotCheckPermissionsWhenUserISOwnerOfPullRequest(): void
    {
        $user           = UserTestBuilder::anActiveUser()->withId($this->pullrequest->getUserId())->build();
        $project_id     = 104;
        $representation = new PullRequestPATCHRepresentation(PullRequest::STATUS_REVIEW, "My PR", "a description", Comment::FORMAT_TEXT);

        $this->pull_request_is_mergeable_checker->expects(self::never())->method("checkUserCanMerge");
        $this->factory->expects(self::once())->method("updateTitle");
        $this->factory->expects(self::once())->method("updateDescription");

        $this->info_updater->patchInfo($user, $this->pullrequest, $project_id, $representation);
    }

    public function testItThrowsAnExceptionWhenTitleIsEmpty(): void
    {
        $user           = UserTestBuilder::anActiveUser()->withId($this->pullrequest->getUserId())->build();
        $project_id     = 104;
        $representation = new PullRequestPATCHRepresentation(PullRequest::STATUS_REVIEW, "", "a description", Comment::FORMAT_TEXT);

        $this->expectExceptionCode(400);

        $this->info_updater->patchInfo($user, $this->pullrequest, $project_id, $representation);
    }

    public function testIntegratorCanUpdatePullRequest(): void
    {
        $user           = UserTestBuilder::anActiveUser()->withId($this->pullrequest->getUserId())->build();
        $project_id     = 104;
        $representation = new PullRequestPATCHRepresentation(PullRequest::STATUS_REVIEW, "My PR", "a description", Comment::FORMAT_TEXT);

        $this->factory->expects(self::once())->method("updateTitle");
        $this->factory->expects(self::once())->method("updateDescription");

        $this->info_updater->patchInfo($user, $this->pullrequest, $project_id, $representation);
    }

    public function testWhenNoFormatIsDefinedDefaultFormatIsText(): void
    {
        $user           = UserTestBuilder::anActiveUser()->withId($this->pullrequest->getUserId())->build();
        $project_id     = 104;
        $representation = new PullRequestPATCHRepresentation(PullRequest::STATUS_REVIEW, "My PR", "a description", null);

        $this->factory->expects(self::once())->method("updateTitle");
        $this->factory->expects(self::once())->method("updateDescription")->with(
            $user,
            $this->pullrequest,
            $project_id,
            "a description",
            Comment::FORMAT_TEXT
        );

        $this->info_updater->patchInfo($user, $this->pullrequest, $project_id, $representation);
    }
}
