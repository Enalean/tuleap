<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git;

use Git_Backend_Interface;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class GitRepositoryCanDeletedTest extends TestCase
{
    private Git_Backend_Interface&MockObject $backend;
    private GitRepository $repo;

    public function setUp(): void
    {
        $this->backend = $this->createMock(Git_Backend_Interface::class);
        $this->backend->method('getGitRootPath')->willReturn(dirname(__FILE__) . '/_fixtures');
        $project = ProjectTestBuilder::aProject()->withUnixName('perms')->build();

        $this->repo = new GitRepository();
        $this->repo->setBackend($this->backend);
        $this->repo->setProject($project);
    }

    public function testItCanBeDeletedWithDotGitDotGitRepositoryShouldSucceed(): void
    {
        $this->backend->method('canBeDeleted')->willReturn(true);
        $this->repo->setPath('perms/coincoin.git.git');

        self::assertTrue($this->repo->canBeDeleted());
    }

    public function testItCanBeDeletedWithWrongRepositoryPathShouldFail(): void
    {
        $this->backend->method('canBeDeleted')->willReturn(true);
        $this->repo->setPath('perms/coincoin');

        self::assertFalse($this->repo->canBeDeleted());
    }

    public function testItCannotBeDeletedIfBackendForbidIt(): void
    {
        $this->backend->method('canBeDeleted')->willReturn(false);

        $this->repo->setPath('perms/coincoin.git.git');
        self::assertFalse($this->repo->canBeDeleted());
    }
}
