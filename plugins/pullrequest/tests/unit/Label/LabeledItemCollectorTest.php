<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Label;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class LabeledItemCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private \UserManager&MockObject $user_manager;
    private \GitRepositoryFactory&MockObject $repository_factory;
    private \Tuleap\PullRequest\Reference\HTMLURLBuilder&MockObject $html_url_builder;
    private LabeledItemCollection&MockObject $item_collection;
    private PullRequestLabelDao&MockObject $label_dao;
    private PullRequestPermissionChecker&MockObject $pullrequest_permission_checker;
    private GlyphFinder&MockObject $glyph_finder;
    private int $project_id;
    private array $label_ids;
    private \UserHelper&MockObject $user_helper;
    private \TemplateRenderer&MockObject $template_renderer;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pullrequest_permission_checker = $this->createMock(\Tuleap\PullRequest\Authorization\PullRequestPermissionChecker::class);
        $this->label_dao                      = $this->createMock(\Tuleap\PullRequest\Label\PullRequestLabelDao::class);
        $this->glyph_finder                   = $this->createMock(\Tuleap\Glyph\GlyphFinder::class);

        $glyph = $this->createMock(\Tuleap\Glyph\Glyph::class);
        $this->glyph_finder->method('get')->willReturn($glyph);

        $this->label_ids = [19, 27];

        $this->item_collection = $this->mockLabeledItemCollection();

        $this->label_dao->method('foundRows')->willReturn(99);

        $first_pullrequest  = PullRequestTestBuilder::aPullRequestInReview()->withId(75)->withTitle('First PR')->withRepositoryDestinationId(2)->createdBy(101)->build();
        $second_pullrequest = PullRequestTestBuilder::aPullRequestInReview()->withId(66)->withTitle('Second PR')->withRepositoryDestinationId(2)->createdBy(101)->build();


        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest(
            $first_pullrequest,
            $second_pullrequest
        );

        $this->html_url_builder = $this->createMock(\Tuleap\PullRequest\Reference\HTMLURLBuilder::class);

        $this->repository_factory = $this->createMock(\GitRepositoryFactory::class);
        $repository               = $this->createMock(\GitRepository::class);
        $repository->method('getName')->willReturn('repo001');
        $repository->method('getHTMLLink')->willReturn('');
        $this->repository_factory->method('getRepositoryById')->willReturn($repository);

        $this->user_manager = $this->createMock(\UserManager::class);
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::aUser()->withRealName('user1')->build());

        $this->user_helper       = $this->createMock(\UserHelper::class);
        $this->template_renderer = $this->createMock(\TemplateRenderer::class);

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testItThrowsExceptionIfThePullRequestIsNotFoundInDB(): void
    {
        $this->pull_request_dao = SearchPullRequestStub::withNoRow();
        $this->label_dao->method('searchPullRequestsByLabels')->willReturn(\TestHelper::argListToDar([
            ['id' => 75],
            ['id' => 66],
        ]));
        $this->item_collection->expects(self::never())->method('add');

        $this->expectException(\LogicException::class);

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItCollectsPullRequestsWithTheGivenLabel(): void
    {
        $this->label_dao
            ->expects(self::once())
            ->method('searchPullRequestsByLabels')
            ->with($this->project_id, $this->label_ids, 50, 0)
            ->willReturn(\TestHelper::argListToDar([
                ['id' => 75],
                ['id' => 66],
            ]));

        $this->pullrequest_permission_checker->method('checkPullRequestIsReadableByUser');

        $this->user_helper->method('getLinkOnUser')->willReturn('');
        $this->template_renderer->method('renderToString')->willReturn('');
        $this->html_url_builder->method('getPullRequestOverviewUrl');

        $this->item_collection->expects(self::exactly(2))->method('add');
        $this->item_collection->expects(self::once())->method('setTotalSize')->with(99);

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsUserCannotSee(): void
    {
        $this->label_dao->method('searchPullRequestsByLabels')->willReturn(\TestHelper::argListToDar([
            ['id' => 75],
            ['id' => 66],
        ]));

        $this->pullrequest_permission_checker->method('checkPullRequestIsReadableByUser')->willThrowException(new UserCannotReadGitRepositoryException());
        $this->item_collection->expects(self::never())->method('add');
        $this->item_collection->expects(self::atLeast(1))->method('thereAreItemsUserCannotSee');

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsFromProjectsUserCannotSee(): void
    {
        $this->label_dao->method('searchPullRequestsByLabels')->willReturn(\TestHelper::argListToDar([
            ['id' => 75],
            ['id' => 66],
        ]));

        $this->pullrequest_permission_checker->method('checkPullRequestIsReadableByUser')->willThrowException(new \Project_AccessPrivateException());
        $this->item_collection->expects(self::never())->method('add');
        $this->item_collection->expects(self::atLeast(1))->method('thereAreItemsUserCannotSee');

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsWhenNotFound(): void
    {
        $this->label_dao->method('searchPullRequestsByLabels')->willReturn(\TestHelper::argListToDar([
            ['id' => 75],
            ['id' => 66],
        ]));

        $this->pullrequest_permission_checker->method('checkPullRequestIsReadableByUser')->willThrowException(new \GitRepoNotFoundException());
        $this->item_collection->expects(self::never())->method('add');
        $this->item_collection->expects(self::never())->method('thereAreItemsUserCannotSee');

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsWhenProjectNotFound(): void
    {
        $this->label_dao->method('searchPullRequestsByLabels')->willReturn(\TestHelper::argListToDar([
            ['id' => 75],
            ['id' => 66],
        ]));

        $this->pullrequest_permission_checker->method('checkPullRequestIsReadableByUser')->willThrowException(new \Project_AccessProjectNotFoundException());
        $this->item_collection->expects(self::never())->method('add');
        $this->item_collection->expects(self::never())->method('thereAreItemsUserCannotSee');

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    private function mockLabeledItemCollection(): LabeledItemCollection&MockObject
    {
        $collection = $this->createMock(\Tuleap\Label\LabeledItemCollection::class);

        $this->project_id = 174;
        $limit            = 50;
        $offset           = 0;
        $project          = ProjectTestBuilder::aProject()->withId($this->project_id)->withAccessPrivate()->build();
        $user             = UserTestBuilder::aUser()->withId(265)->build();

        $collection->method('getLabelIds')->willReturn($this->label_ids);
        $collection->method('getProject')->willReturn($project);
        $collection->method('getUser')->willReturn($user);
        $collection->method('getLimit')->willReturn($limit);
        $collection->method('getOffset')->willReturn($offset);
        $collection->method('setTotalSize');

        return $collection;
    }

    private function instantiateCollector(): LabeledItemCollector
    {
        return new LabeledItemCollector(
            $this->label_dao,
            new PullRequestRetriever($this->pull_request_dao),
            $this->pullrequest_permission_checker,
            $this->html_url_builder,
            $this->glyph_finder,
            $this->repository_factory,
            $this->user_manager,
            $this->user_helper,
            $this->createMock(\Git_GitRepositoryUrlManager::class),
            $this->template_renderer,
        );
    }
}
