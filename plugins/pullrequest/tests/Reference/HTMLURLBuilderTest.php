<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

require_once __DIR__.'/../bootstrap.php';

class HTMLURLBuilderTest extends \TuleapTestCase
{
    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var int
     */
    private $repository_id;
    /**
     * @var int
     */
    private $project_id;

    public function setUp()
    {
        parent::setUp();
        $this->repository_id = 8;
        $this->project_id    = 109;

        $this->git_repository_factory = mock('GitRepositoryFactory');
        $repository                   = mock('GitRepository');
        $project                      = aMockProject()->withId($this->project_id)->build();
        stub($repository)->getProject()->returns($project);
        stub($this->git_repository_factory)->getRepositoryById($this->repository_id)->returns($repository);
    }

    public function itReturnsTheWebURLToPullRequestOverview()
    {
        $pull_request = mock('Tuleap\\PullRequest\\PullRequest');
        stub($pull_request)->getId()->returns(27);
        stub($pull_request)->getRepositoryId()->returns($this->repository_id);

        $html_url_builder = new HTMLURLBuilder(
            $this->git_repository_factory
        );

        $result = $html_url_builder->getPullRequestOverviewUrl($pull_request);

        $expected_url = '/plugins/git/?action=pull-requests&repo_id=8&group_id=109#/pull-requests/27/overview';

        $this->assertEqual($expected_url, $result);
    }
}
