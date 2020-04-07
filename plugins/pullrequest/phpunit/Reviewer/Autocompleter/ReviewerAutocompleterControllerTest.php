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

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
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
use UserManager;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class ReviewerAutocompleterControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Factory
     */
    private $pull_request_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PotentialReviewerRetriever
     */
    private $potential_reviewer_retriever;

    /**
     * @var ReviewerAutocompleterController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->user_manager                    = Mockery::mock(UserManager::class);
        $this->pull_request_factory            = Mockery::mock(Factory::class);
        $this->pull_request_permission_checker = Mockery::mock(PullRequestPermissionChecker::class);
        $this->potential_reviewer_retriever    = Mockery::mock(PotentialReviewerRetriever::class);

        $this->controller = new ReviewerAutocompleterController(
            $this->user_manager,
            $this->pull_request_factory,
            $this->pull_request_permission_checker,
            $this->potential_reviewer_retriever,
            new JSONResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            Mockery::mock(EmitterInterface::class)
        );
    }

    public function testReturnsJSONEncodedListOfPotentialReviewers(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('pull_request_id')->andReturn('123');
        $server_request->shouldReceive('getQueryParams')->andReturn(['name' => 'review']);

        $pull_request = Mockery::mock(PullRequest::class);
        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(123)->andReturn($pull_request);

        $current_user = $this->buildUser(99, 'pr_owner');
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsMergeableByUser')
            ->with($pull_request, $current_user);

        $this->potential_reviewer_retriever->shouldReceive('getPotentialReviewers')
            ->andReturn([
                $this->buildUser(78, 'reviewer1'),
                $this->buildUser(79, 'reviewer2'),
            ]);

        $response = $this->controller->handle($server_request);

        $this->assertEquals(200, $response->getStatusCode());

        $decoded_response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $decoded_response);
        $this->assertEquals(78, $decoded_response[0]['id']);
        $this->assertEquals(79, $decoded_response[1]['id']);
    }

    private function buildUser(int $id, string $username): PFUser
    {
        return new PFUser(['user_id' => $id, 'user_name' => $username]);
    }

    public function testNotFoundIfThePullRequestDoesNotExist(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('pull_request_id')->andReturn('404');

        $this->pull_request_factory->shouldReceive('getPullRequestById')
            ->andThrow(PullRequestNotFoundException::class);


        $this->expectException(NotFoundException::class);
        $this->controller->handle($server_request);
    }

    public function testOnlyUserThatCanMergeThePRCanUseItsAutocompleter(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('pull_request_id')->andReturn('124');

        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(124);
        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(124)->andReturn($pull_request);

        $current_user = $this->buildUser(101, 'random_user_with_no_merge_capability');
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsMergeableByUser')
            ->andThrow(new UserCannotMergePullRequestException($pull_request, $current_user));

        $this->expectException(NotFoundException::class);
        $this->controller->handle($server_request);
    }

    public function testRequestIsRejectedWhenTheNameToSearchIsEmpty(): void
    {
        $server_request = Mockery::mock(ServerRequestInterface::class);
        $server_request->shouldReceive('getAttribute')->with('pull_request_id')->andReturn('126');
        $server_request->shouldReceive('getQueryParams')->andReturn(['name' => '']);

        $pull_request = Mockery::mock(PullRequest::class);
        $this->pull_request_factory->shouldReceive('getPullRequestById')->with(126)->andReturn($pull_request);

        $current_user = $this->buildUser(99, 'pr_owner');
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsMergeableByUser')
            ->with($pull_request, $current_user);

        $response = $this->controller->handle($server_request);

        $this->assertEquals(400, $response->getStatusCode());
    }
}
