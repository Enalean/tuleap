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

namespace Tuleap\PullRequest\Reviewer\Autocompleter;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class ReviewerAutocompleterControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private MockObject&UserManager $user_manager;
    private MockObject&PullRequestPermissionChecker $pull_request_permission_checker;
    private MockObject&PotentialReviewerRetriever $potential_reviewer_retriever;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->user_manager                    = $this->createMock(UserManager::class);
        $this->pull_request_dao                = SearchPullRequestStub::withNoRow();
        $this->pull_request_permission_checker = $this->createMock(PullRequestPermissionChecker::class);
        $this->potential_reviewer_retriever    = $this->createMock(PotentialReviewerRetriever::class);
    }

    private function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = new ReviewerAutocompleterController(
            $this->user_manager,
            new PullRequestRetriever($this->pull_request_dao),
            $this->pull_request_permission_checker,
            $this->potential_reviewer_retriever,
            new JSONResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            $this->createMock(EmitterInterface::class)
        );

        return $controller->handle($request);
    }

    public function testReturnsJSONEncodedListOfPotentialReviewers(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('123');
        $server_request->method('getQueryParams')->willReturn(['name' => 'review']);

        $pull_request           = PullRequestTestBuilder::aPullRequestInReview()->withId(123)->build();
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $current_user = UserTestBuilder::aUser()->withId(101)->withUserName('pr_owner')->withRealName('pull_request')->build();
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $this->pull_request_permission_checker->method('checkPullRequestIsMergeableByUser')
            ->with($pull_request, $current_user);

        $this->potential_reviewer_retriever->method('getPotentialReviewers')
            ->willReturn([
                UserTestBuilder::aUser()->withId(105)->withUserName('reviewer1')->withRealName('first')->build(),
                UserTestBuilder::aUser()->withId(106)->withUserName('reviewer2')->withRealName('second')->build(),
            ]);

        $response = $this->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());

        $decoded_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $decoded_response);
        self::assertSame(105, $decoded_response[0]['id']);
        self::assertSame(106, $decoded_response[1]['id']);
    }

    public function testNotFoundIfThePullRequestDoesNotExist(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('404');

        $this->expectException(NotFoundException::class);
        $this->handle($server_request);
    }

    public function testOnlyUserThatCanMergeThePRCanUseItsAutocompleter(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('124');

        $pull_request           = PullRequestTestBuilder::aPullRequestInReview()->withId(124)->build();
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $current_user =  UserTestBuilder::aUser()->withId(101)->withUserName('random_user_with_no_merge_capability')->withRealName('paysan')->build();
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $this->pull_request_permission_checker->method('checkPullRequestIsMergeableByUser')
            ->willThrowException(new UserCannotMergePullRequestException($pull_request, $current_user));

        $this->expectException(NotFoundException::class);
        $this->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheNameToSearchIsEmpty(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('126');
        $server_request->method('getQueryParams')->willReturn(['name' => '']);

        $pull_request           = PullRequestTestBuilder::aPullRequestInReview()->withId(126)->build();
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $current_user =  UserTestBuilder::aUser()->withId(105)->withUserName('pr_owner')->withRealName('boss')->build();
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $this->pull_request_permission_checker->method('checkPullRequestIsMergeableByUser')
            ->with($pull_request, $current_user);

        $response = $this->handle($server_request);

        self::assertEquals(400, $response->getStatusCode());
    }
}
