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

use PFUser;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class ReviewerAutocompleterControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Factory
     */
    private $pull_request_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PotentialReviewerRetriever
     */
    private $potential_reviewer_retriever;
    private ReviewerAutocompleterController $controller;

    protected function setUp(): void
    {
        $this->user_manager                    = $this->createMock(UserManager::class);
        $this->pull_request_factory            = $this->createMock(Factory::class);
        $this->pull_request_permission_checker = $this->createMock(PullRequestPermissionChecker::class);
        $this->potential_reviewer_retriever    = $this->createMock(PotentialReviewerRetriever::class);

        $this->controller = new ReviewerAutocompleterController(
            $this->user_manager,
            $this->pull_request_factory,
            $this->pull_request_permission_checker,
            $this->potential_reviewer_retriever,
            new JSONResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            $this->createMock(EmitterInterface::class)
        );
    }

    public function testReturnsJSONEncodedListOfPotentialReviewers(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('123');
        $server_request->method('getQueryParams')->willReturn(['name' => 'review']);

        $pull_request = $this->createMock(PullRequest::class);
        $this->pull_request_factory->method('getPullRequestById')->with(123)->willReturn($pull_request);

        $current_user = $this->buildUser(99, 'pr_owner', 'pull_request');
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $this->pull_request_permission_checker->method('checkPullRequestIsMergeableByUser')
            ->with($pull_request, $current_user);

        $this->potential_reviewer_retriever->method('getPotentialReviewers')
            ->willReturn([
                $this->buildUser(78, 'reviewer1', 'first'),
                $this->buildUser(79, 'reviewer2', 'second'),
            ]);

        $response = $this->controller->handle($server_request);

        self::assertEquals(200, $response->getStatusCode());

        $decoded_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $decoded_response);
        self::assertEquals(78, $decoded_response[0]['id']);
        self::assertEquals(79, $decoded_response[1]['id']);
    }

    private function buildUser(int $id, string $username, string $realname): PFUser
    {
        return UserTestBuilder::aUser()->withId($id)->withUserName($username)->withRealName($realname)->build();
    }

    public function testNotFoundIfThePullRequestDoesNotExist(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('404');

        $this->pull_request_factory->method('getPullRequestById')
            ->willThrowException(new PullRequestNotFoundException());


        $this->expectException(NotFoundException::class);
        $this->controller->handle($server_request);
    }

    public function testOnlyUserThatCanMergeThePRCanUseItsAutocompleter(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('124');

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(124);
        $this->pull_request_factory->method('getPullRequestById')->with(124)->willReturn($pull_request);

        $current_user = $this->buildUser(101, 'random_user_with_no_merge_capability', 'paysan');
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $this->pull_request_permission_checker->method('checkPullRequestIsMergeableByUser')
            ->willThrowException(new UserCannotMergePullRequestException($pull_request, $current_user));

        $this->expectException(NotFoundException::class);
        $this->controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheNameToSearchIsEmpty(): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getAttribute')->with('pull_request_id')->willReturn('126');
        $server_request->method('getQueryParams')->willReturn(['name' => '']);

        $pull_request = $this->createMock(PullRequest::class);
        $this->pull_request_factory->method('getPullRequestById')->with(126)->willReturn($pull_request);

        $current_user = $this->buildUser(99, 'pr_owner', 'boss');
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);

        $this->pull_request_permission_checker->method('checkPullRequestIsMergeableByUser')
            ->with($pull_request, $current_user);

        $response = $this->controller->handle($server_request);

        self::assertEquals(400, $response->getStatusCode());
    }
}
