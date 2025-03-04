<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use Git_GitRepositoryUrlManager;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RepositoryHeaderTabsURLBuilderTest extends TestCase
{
    private readonly RepositoryHeaderTabsURLBuilder $builder;
    private readonly \PHPUnit\Framework\MockObject\MockObject&Git_GitRepositoryUrlManager $url_manager;

    protected function setUp(): void
    {
        $this->url_manager = $this->createMock(Git_GitRepositoryUrlManager::class);
        $this->builder     = new RepositoryHeaderTabsURLBuilder($this->url_manager);
    }

    public function testItBuildsFilesTabURLWithBranchInformation(): void
    {
        $request = new \HTTPRequest();
        $request->set('hb', 'branch01');

        $repository = new \GitRepository();

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('repository_url');

        self::assertSame(
            'repository_url?a=tree&hb=branch01',
            $this->builder->buildFilesTabURL($repository, $request),
        );
    }

    public function testItBuildsFilesTabURLWithCommitInformation(): void
    {
        $request = new \HTTPRequest();
        $request->set('h', 'commit01');

        $repository = new \GitRepository();

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('repository_url');

        self::assertSame(
            'repository_url?a=tree&hb=commit01',
            $this->builder->buildFilesTabURL($repository, $request),
        );
    }

    public function testItBuildsFilesTabURLWithoutAnyInformation(): void
    {
        $request    = new \HTTPRequest();
        $repository = new \GitRepository();

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('repository_url');

        self::assertSame(
            'repository_url',
            $this->builder->buildFilesTabURL($repository, $request),
        );
    }

    public function testItBuildsCommitsTabURLWithBranchInformation(): void
    {
        $request = new \HTTPRequest();
        $request->set('hb', 'branch01');

        $repository = new \GitRepository();

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('repository_url');

        self::assertSame(
            'repository_url?a=shortlog&hb=branch01',
            $this->builder->buildCommitsTabURL($repository, $request),
        );
    }

    public function testItBuildsCommitsTabURLWithCommitInformation(): void
    {
        $request = new \HTTPRequest();
        $request->set('h', 'commit01');

        $repository = new \GitRepository();

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('repository_url');

        self::assertSame(
            'repository_url?a=commit&h=commit01',
            $this->builder->buildCommitsTabURL($repository, $request),
        );
    }

    public function testItBuildsCommitsTabURLWithoutAnyInformation(): void
    {
        $request    = new \HTTPRequest();
        $repository = new \GitRepository();

        $this->url_manager->method('getRepositoryBaseUrl')->with($repository)->willReturn('repository_url');

        self::assertSame(
            'repository_url?a=shortlog',
            $this->builder->buildCommitsTabURL($repository, $request),
        );
    }
}
