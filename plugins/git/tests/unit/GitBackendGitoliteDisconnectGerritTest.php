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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitBackendGitoliteDisconnectGerritTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $repo_id = 123;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|GitRepository
     */
    private $repository;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Git_Backend_Gitolite
     */
    private $backend;

    /**
     * @var GitDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(GitRepository::class)->shouldReceive('getId')->andReturn($this->repo_id)->getMock();
        $this->dao        = \Mockery::spy(GitDao::class);
        $this->backend    = \Mockery::mock(\Git_Backend_Gitolite::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->backend->setDao($this->dao);
    }

    public function testItAsksToDAOToDisconnectFromGerrit(): void
    {
        $this->dao->shouldReceive('disconnectFromGerrit')->with($this->repo_id)->once();

        $this->backend->disconnectFromGerrit($this->repository);
    }
}
