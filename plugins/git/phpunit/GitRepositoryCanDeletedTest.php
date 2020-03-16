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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryCanDeletedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();

        $this->backend = \Mockery::spy(\GitBackend::class)->shouldReceive('getGitRootPath')->andReturns(dirname(__FILE__) . '/_fixtures')->getMock();
        $project       = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('perms')->getMock();

        $this->repo = new GitRepository();
        $this->repo->setBackend($this->backend);
        $this->repo->setProject($project);
    }

    public function testItCanBeDeletedWithDotGitDotGitRepositoryShouldSucceed()
    {
        $this->backend->shouldReceive('canBeDeleted')->andReturns(true);
        $this->repo->setPath('perms/coincoin.git.git');

        $this->assertTrue($this->repo->canBeDeleted());
    }

    public function testItCanBeDeletedWithWrongRepositoryPathShouldFail()
    {
        $this->backend->shouldReceive('canBeDeleted')->andReturns(true);
        $this->repo->setPath('perms/coincoin');

        $this->assertFalse($this->repo->canBeDeleted());
    }

    public function testItCannotBeDeletedIfBackendForbidIt()
    {
        $this->backend->shouldReceive('canBeDeleted')->andReturns(false);

        $this->repo->setPath('perms/coincoin.git.git');
        $this->assertFalse($this->repo->canBeDeleted());
    }
}
