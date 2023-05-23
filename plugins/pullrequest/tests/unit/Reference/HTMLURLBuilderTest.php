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
use Tuleap\ForgeConfigSandbox;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestV2FeatureFlag;

final class HTMLURLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

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
    /**
     * @var HTMLURLBuilder
     */
    private $html_url_builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository_id = 8;
        $this->project_id    = 109;

        $this->git_repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $repository                   = \Mockery::spy(\GitRepository::class);
        $project                      = \Mockery::spy(\Project::class, ['getID' => $this->project_id, 'getUserName' => false, 'isPublic' => false]);
        $repository->shouldReceive('getProject')->andReturns($project);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->with($this->repository_id)->andReturns($repository);

        $this->html_url_builder = new HTMLURLBuilder(
            $this->git_repository_factory
        );

        \ForgeConfig::set("feature_flag_" . PullRequestV2FeatureFlag::FEATURE_FLAG_KEY, "1");
    }

    public function testItReturnsTheWebURLToPullRequestOverview(): void
    {
        $result = $this->html_url_builder->getPullRequestOverviewUrl($this->buildPullRequest(27));

        $expected_url = '/plugins/git/?action=pull-requests&repo_id=8&group_id=109#/pull-requests/27/overview';

        $this->assertEquals($expected_url, $result);
    }

    public function testItReturnsTheAbsoluteWebURLToPullRequestOverview(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $result = $this->html_url_builder->getAbsolutePullRequestOverviewUrl($this->buildPullRequest(28));

        $expected_url = 'https://example.com/plugins/git/?action=pull-requests&repo_id=8&group_id=109#/pull-requests/28/overview';

        $this->assertEquals($expected_url, $result);
    }

    private function buildPullRequest(int $pull_request_id): PullRequest
    {
        $pull_request = \Mockery::mock(\Tuleap\PullRequest\PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturns($pull_request_id);
        $pull_request->shouldReceive('getRepositoryId')->andReturns($this->repository_id);

        return $pull_request;
    }
}
