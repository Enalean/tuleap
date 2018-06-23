<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\Label;

use Tuleap\Glyph\GlyphFinder;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Factory;
use TuleapTestCase;

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../../../../src/www/include/utils.php';

class LabeledItemCollectorTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();
        $this->pullrequest_permission_checker = mock(
            'Tuleap\\PullRequest\\Authorization\\PullRequestPermissionChecker'
        );
        $this->label_dao           = mock('Tuleap\\PullRequest\\Label\\PullRequestLabelDao');
        $this->glyph_finder        = mock('Tuleap\\Glyph\\GlyphFinder');
        $this->pullrequest_factory = mock('Tuleap\\PullRequest\\Factory');

        $glyph = mock('Tuleap\\Glyph\\Glyph');
        stub($this->glyph_finder)->get()->returns($glyph);

        $this->label_ids = array(19, 27);

        $this->item_collection = $this->mockLabeledItemCollection();

        stub($this->label_dao)->searchPullRequestsByLabels()->returnsDarFromArray(array(
            array('id' => 75),
            array('id' => 66)
        ));
        stub($this->label_dao)->foundRows()->returns(99);

        $first_pullrequest  = mock('Tuleap\\PullRequest\\PullRequest');
        stub($first_pullrequest)->getTitle()->returns('First PR');
        $second_pullrequest = mock('Tuleap\\PullRequest\\PullRequest');
        stub($second_pullrequest)->getTitle()->returns('Second PR');
        stub($this->pullrequest_factory)->getPullRequestById(75)->returns($first_pullrequest);
        stub($this->pullrequest_factory)->getPullRequestById(66)->returns($second_pullrequest);

        $this->html_url_builder = mock('Tuleap\PullRequest\Reference\HTMLURLBuilder');


        $this->repository_factory = mock('GitRepositoryFactory');
        $repository = mock('GitRepository');
        stub($repository)->getName()->returns('repo001');
        stub($this->repository_factory)->getRepositoryById()->returns($repository);

        $this->user_manager = mock('UserManager');
        $user = mock('PFUser');
        stub($user)->getRealName()->returns('user1');
        stub($this->user_manager)->getUserById()->returns($user);
    }

    public function itCollectsPullRequestsWithTheGivenLabel()
    {
        expect($this->label_dao)->searchPullRequestsByLabels(
            $this->project_id,
            $this->label_ids,
            50,
            0
        )->once();
        $this->item_collection->expectCallCount('add', 2);
        expect($this->item_collection)->setTotalSize(99)->once();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function itDoesNotAddPullRequestsUserCannotSee()
    {
        stub($this->pullrequest_permission_checker)->checkPullRequestIsReadableByUser()->throws(
            new UserCannotReadGitRepositoryException()
        );
        $this->item_collection->expectCallCount('add', 0);
        expect($this->item_collection)->thereAreItemsUserCannotSee()->atLeastOnce();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function itDoesNotAddPullRequestsFromProjectsUserCannotSee()
    {
        stub($this->pullrequest_permission_checker)->checkPullRequestIsReadableByUser()->throws(
            new \Project_AccessPrivateException()
        );
        $this->item_collection->expectCallCount('add', 0);
        expect($this->item_collection)->thereAreItemsUserCannotSee()->atLeastOnce();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function itDoesNotAddPullRequestsWhenNotFound()
    {
        stub($this->pullrequest_permission_checker)->checkPullRequestIsReadableByUser()->throws(
            new \GitRepoNotFoundException()
        );
        $this->item_collection->expectCallCount('add', 0);
        expect($this->item_collection)->thereAreItemsUserCannotSee()->never();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    public function itDoesNotAddPullRequestsWhenProjectNotFound()
    {
        stub($this->pullrequest_permission_checker)->checkPullRequestIsReadableByUser()->throws(
            new \Project_AccessProjectNotFoundException()
        );
        $this->item_collection->expectCallCount('add', 0);
        expect($this->item_collection)->thereAreItemsUserCannotSee()->never();

        $collector = $this->instantiateCollector();
        $collector->collect($this->item_collection);
    }

    private function mockLabeledItemCollection()
    {
        $collection = mock('Tuleap\\Label\\LabeledItemCollection');

        $this->project_id = 174;
        $limit            = 50;
        $offset           = 0;
        $project          = aMockProject()->withId($this->project_id)->build();
        $user             = aUser()->withId(265)->build();

        stub($collection)->getLabelIds()->returns($this->label_ids);
        stub($collection)->getProject()->returns($project);
        stub($collection)->getUser()->returns($user);
        stub($collection)->getLimit()->returns($limit);
        stub($collection)->getOffset()->returns($offset);

        return $collection;
    }

    private function instantiateCollector()
    {
        return new LabeledItemCollector(
            $this->label_dao,
            $this->pullrequest_factory,
            $this->pullrequest_permission_checker,
            $this->html_url_builder,
            $this->glyph_finder,
            $this->repository_factory,
            $this->user_manager,
            mock('UserHelper'),
            mock('Git_GitRepositoryUrlManager'),
            mock('TemplateRenderer')
        );
    }
}
