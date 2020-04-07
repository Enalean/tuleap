<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\GitPHPDisplay;

use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

class DownloadURLBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $repository;
    private $user;
    private $download_url_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturn(1);

        $this->user = Mockery::mock(PFUser::class);

        $this->download_url_builder = new DownloadURLBuilder();
    }

    public function testItBuildsTheDownloadURL()
    {
        $file = <<<EOS
version https://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12

EOS;
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturn(true);

        $expected = 'plugins/git-lfs/1/objects/eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a';

        $this->assertSame($expected, $this->download_url_builder->buildDownloadURL($this->repository, $this->user, $file));
    }

    public function testItReturnsEmptyUrlIfFileIsNotWellFormed()
    {
        $file = <<<EOS
version https://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4
size 12

EOS;
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturn(true);

        $this->assertSame('', $this->download_url_builder->buildDownloadURL($this->repository, $this->user, $file));
    }



    public function testItReturnsEmptyUrlIfUserCannotReadTheRepository()
    {
        $file = <<<EOS
version https://git-lfs.github.com/spec/v1
oid sha256:eca87262c137f4847c6a78fcb8e035a7cb725570f5a70af91c591d77fa7e9d1a
size 12

EOS;

        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturn(false);

        $this->assertSame('', $this->download_url_builder->buildDownloadURL($this->repository, $this->user, $file));
    }
}
