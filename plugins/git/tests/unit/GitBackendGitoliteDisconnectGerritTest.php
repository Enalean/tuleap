<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

declare(strict_types=1);

namespace Tuleap\Git;

use Git_Backend_Gitolite;
use GitDao;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitBackendGitoliteDisconnectGerritTest extends TestCase
{
    private int $repo_id = 123;
    private GitRepository $repository;
    private Git_Backend_Gitolite&MockObject $backend;
    private GitDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId($this->repo_id)->build();
        $this->dao        = $this->createMock(GitDao::class);
        $this->backend    = $this->createPartialMock(Git_Backend_Gitolite::class, []);
        $this->backend->setDao($this->dao);
    }

    public function testItAsksToDAOToDisconnectFromGerrit(): void
    {
        $this->dao->expects($this->once())->method('disconnectFromGerrit')->with($this->repo_id);

        $this->backend->disconnectFromGerrit($this->repository);
    }
}
