<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../bootstrap.php';

class HTMLURLBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository_id = 8;
        $this->project_id    = 109;

        $this->git_repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $repository                   = \Mockery::spy(\GitRepository::class);
        $project                      = \Mockery::spy(\Project::class, ['getID' => $this->project_id, 'getUnixName' => false, 'isPublic' => false]);
        $repository->shouldReceive('getProject')->andReturns($project);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->with($this->repository_id)->andReturns($repository);
    }

    public function testItReturnsTheWebURLToPullRequestOverview(): void
    {
        $pull_request = \Mockery::spy(\Tuleap\PullRequest\PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturns(27);
        $pull_request->shouldReceive('getRepositoryId')->andReturns($this->repository_id);

        $html_url_builder = new HTMLURLBuilder(
            $this->git_repository_factory
        );

        $result = $html_url_builder->getPullRequestOverviewUrl($pull_request);

        $expected_url = '/plugins/git/?action=pull-requests&repo_id=8&group_id=109#/pull-requests/27/overview';

        $this->assertEquals($expected_url, $result);
    }
}
