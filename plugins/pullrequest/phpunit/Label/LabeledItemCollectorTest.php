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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Factory;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../../../src/www/include/utils.php';

class LabeledItemCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \Tuleap\PullRequest\Reference\HTMLURLBuilder
     */
    private $html_url_builder;
    /**
     * @var LabeledItemCollection
     */
    private $item_collection;
    /**
     * @var PullRequestLabelDao
     */
    private $label_dao;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pullrequest_permission_checker;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;
    /**
     * @var Factory
     */
    private $pullrequest_factory;
    /**
     * @var int
     */
    private $project_id;
    /**
     * @var array
     */
    private $label_ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pullrequest_permission_checker = \Mockery::spy(\Tuleap\PullRequest\Authorization\PullRequestPermissionChecker::class);
        $this->label_dao           = \Mockery::spy(\Tuleap\PullRequest\Label\PullRequestLabelDao::class);
        $this->glyph_finder        = \Mockery::spy(\Tuleap\Glyph\GlyphFinder::class);
        $this->pullrequest_factory = \Mockery::spy(\Tuleap\PullRequest\Factory::class);

        $glyph = \Mockery::spy(\Tuleap\Glyph\Glyph::class);
        $this->glyph_finder->shouldReceive('get')->andReturns($glyph);

        $this->label_ids = array(19, 27);

        $this->item_collection = $this->mockLabeledItemCollection();

        $this->label_dao->shouldReceive('foundRows')->andReturns(99);

        $first_pullrequest  = \Mockery::spy(\Tuleap\PullRequest\PullRequest::class);
        $first_pullrequest->shouldReceive('getTitle')->andReturns('First PR');
        $second_pullrequest = \Mockery::spy(\Tuleap\PullRequest\PullRequest::class);
        $second_pullrequest->shouldReceive('getTitle')->andReturns('Second PR');
        $this->pullrequest_factory->shouldReceive('getPullRequestById')->with(75)->andReturns($first_pullrequest);
        $this->pullrequest_factory->shouldReceive('getPullRequestById')->with(66)->andReturns($second_pullrequest);

        $this->html_url_builder = \Mockery::spy(\Tuleap\PullRequest\Reference\HTMLURLBuilder::class);

        $this->repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $repository = \Mockery::spy(\GitRepository::class);
        $repository->shouldReceive('getName')->andReturns('repo001');
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns($repository);

        $this->user_manager = \Mockery::spy(\UserManager::class);
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getRealName')->andReturns('user1');
        $this->user_manager->shouldReceive('getUserById')->andReturns($user);
    }

    public function testItCollectsPullRequestsWithTheGivenLabel(): void
    {
        $this->label_dao->shouldReceive('searchPullRequestsByLabels')
            ->with($this->project_id, $this->label_ids, 50, 0)
            ->once()
            ->andReturns(\TestHelper::argListToDar(array(
                array('id' => 75),
                array('id' => 66)
            )));

        $this->item_collection->shouldReceive('add')->times(2);
        $this->item_collection->shouldReceive('setTotalSize')->with(99)->once();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsUserCannotSee(): void
    {
        $this->label_dao->shouldReceive('searchPullRequestsByLabels')->andReturns(\TestHelper::argListToDar(array(
            array('id' => 75),
            array('id' => 66)
        )));

        $this->pullrequest_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')->andThrows(new UserCannotReadGitRepositoryException());
        $this->item_collection->shouldReceive('add')->never();
        $this->item_collection->shouldReceive('thereAreItemsUserCannotSee')->atLeast()->once();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsFromProjectsUserCannotSee(): void
    {
        $this->label_dao->shouldReceive('searchPullRequestsByLabels')->andReturns(\TestHelper::argListToDar(array(
            array('id' => 75),
            array('id' => 66)
        )));

        $this->pullrequest_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')->andThrows(new \Project_AccessPrivateException());
        $this->item_collection->shouldReceive('add')->never();
        $this->item_collection->shouldReceive('thereAreItemsUserCannotSee')->atLeast()->once();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsWhenNotFound(): void
    {
        $this->label_dao->shouldReceive('searchPullRequestsByLabels')->andReturns(\TestHelper::argListToDar(array(
            array('id' => 75),
            array('id' => 66)
        )));

        $this->pullrequest_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')->andThrows(new \GitRepoNotFoundException());
        $this->item_collection->shouldReceive('add')->never();
        $this->item_collection->shouldReceive('thereAreItemsUserCannotSee')->never();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function testItDoesNotAddPullRequestsWhenProjectNotFound(): void
    {
        $this->label_dao->shouldReceive('searchPullRequestsByLabels')->andReturns(\TestHelper::argListToDar(array(
            array('id' => 75),
            array('id' => 66)
        )));

        $this->pullrequest_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')->andThrows(new \Project_AccessProjectNotFoundException());
        $this->item_collection->shouldReceive('add')->never();
        $this->item_collection->shouldReceive('thereAreItemsUserCannotSee')->never();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    private function mockLabeledItemCollection()
    {
        $collection = \Mockery::spy(\Tuleap\Label\LabeledItemCollection::class);

        $this->project_id = 174;
        $limit            = 50;
        $offset           = 0;
        $project          = \Mockery::spy(\Project::class, ['getID' => $this->project_id, 'getUnixName' => false, 'isPublic' => false]);
        $user             = Mockery::mock(\PFUser::class)->shouldReceive('getId')->andReturn(265)->getMock();

        $collection->shouldReceive('getLabelIds')->andReturns($this->label_ids);
        $collection->shouldReceive('getProject')->andReturns($project);
        $collection->shouldReceive('getUser')->andReturns($user);
        $collection->shouldReceive('getLimit')->andReturns($limit);
        $collection->shouldReceive('getOffset')->andReturns($offset);

        return $collection;
    }

    private function instantiateCollector(): LabeledItemCollector
    {
        return new LabeledItemCollector(
            $this->label_dao,
            $this->pullrequest_factory,
            $this->pullrequest_permission_checker,
            $this->html_url_builder,
            $this->glyph_finder,
            $this->repository_factory,
            $this->user_manager,
            \Mockery::spy(\UserHelper::class),
            \Mockery::spy(\Git_GitRepositoryUrlManager::class),
            \Mockery::spy(\TemplateRenderer::class)
        );
    }
}
