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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class HTMLURLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private \GitRepositoryFactory&MockObject $git_repository_factory;
    private int $repository_id;
    private int $project_id;
    private HTMLURLBuilder $html_url_builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository_id = 8;
        $this->project_id    = 109;

        $this->git_repository_factory = $this->createMock(\GitRepositoryFactory::class);

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId($this->project_id)->build());

        $this->git_repository_factory->method('getRepositoryById')->with($this->repository_id)->willReturn($repository);

        $this->html_url_builder = new HTMLURLBuilder(
            $this->git_repository_factory
        );
    }

    public function testItReturnsTheWebURLToPullRequestOverview(): void
    {
        $result = $this->html_url_builder->getPullRequestOverviewUrl($this->buildPullRequest(27));

        $expected_url = '/plugins/git/?action=pull-requests&repo_id=8&group_id=109&tab=overview#/pull-requests/27/overview';

        self::assertEquals($expected_url, $result);
    }

    public function testItReturnsTheAbsoluteWebURLToPullRequestOverview(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $result = $this->html_url_builder->getAbsolutePullRequestOverviewUrl($this->buildPullRequest(28));

        $expected_url = 'https://example.com/plugins/git/?action=pull-requests&repo_id=8&group_id=109&tab=overview#/pull-requests/28/overview';

        self::assertEquals($expected_url, $result);
    }

    private function buildPullRequest(int $pull_request_id): PullRequest&MockObject
    {
        $pull_request = $this->createMock(\Tuleap\PullRequest\PullRequest::class);
        $pull_request->method('getId')->willReturn($pull_request_id);
        $pull_request->method('getRepositoryId')->willReturn($this->repository_id);

        return $pull_request;
    }
}
