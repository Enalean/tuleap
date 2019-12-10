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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\InstanceBaseURLBuilder;

final class GitRepositoryUrlManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \GitPlugin|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $git_plugin;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|InstanceBaseURLBuilder
     */
    private $instance_base_url_builder;

    /**
     * @var \Git_GitRepositoryUrlManager
     */
    private $url_manager;

    protected function setUp(): void
    {
        $this->git_plugin                = \Mockery::mock(\GitPlugin::class);
        $this->instance_base_url_builder = \Mockery::mock(InstanceBaseURLBuilder::class);

        $this->url_manager = new \Git_GitRepositoryUrlManager($this->git_plugin, $this->instance_base_url_builder);
    }

    public function testBuildsURLToACommit(): void
    {
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getRelativeHTTPUrl')->andReturn('/plugins/git/project1/repo1');

        $this->assertEquals(
            '/plugins/git/project1/repo1?a=commit&h=fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            $this->url_manager->getCommitURL($repository, 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6')
        );
    }

    public function testBuildsAbsoluteURLToACommit(): void
    {
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getRelativeHTTPUrl')->andReturn('/plugins/git/project1/repo1');
        $this->instance_base_url_builder->shouldReceive('build')->andReturn('https://example.com');

        $this->assertEquals(
            'https://example.com/plugins/git/project1/repo1?a=commit&h=fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            $this->url_manager->getAbsoluteCommitURL($repository, 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6')
        );
    }
}
