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

namespace Tuleap\Git;

use ForgeConfig;
use Git_GitRepositoryUrlManager;
use GitPlugin;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryUrlManagerTest extends TestCase
{
    use ForgeConfigSandbox;

    private Git_GitRepositoryUrlManager $url_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->url_manager = new Git_GitRepositoryUrlManager($this->createMock(GitPlugin::class));
    }

    public function testBuildsURLToACommit(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->withName('repo1')
            ->inProject(ProjectTestBuilder::aProject()->withUnixName('project1')->build())->build();

        self::assertEquals(
            '/plugins/git/project1/repo1?a=commit&h=fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            $this->url_manager->getCommitURL($repository, 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6')
        );
    }

    public function testBuildsAbsoluteURLToACommit(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->withName('repo1')
            ->inProject(ProjectTestBuilder::aProject()->withUnixName('project1')->build())->build();
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->assertEquals(
            'https://example.com/plugins/git/project1/repo1?a=commit&h=fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            $this->url_manager->getAbsoluteCommitURL($repository, 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6')
        );
    }
}
