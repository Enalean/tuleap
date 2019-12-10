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

require_once __DIR__.'/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitBackendGitoliteDisconnectGerritTest extends TuleapTestCase
{
    private $repo_id = 123;

    public function setUp()
    {
        parent::setUp();
        $this->repository = aGitRepository()->withId($this->repo_id)->build();
        $this->dao        = \Mockery::spy(GitDao::class);
        $this->backend    = partial_mock('Git_Backend_Gitolite', array('updateRepoConf'));
        $this->backend->setDao($this->dao);
    }

    public function itAsksToDAOToDisconnectFromGerrit()
    {
        $this->dao->shouldReceive('disconnectFromGerrit')->with($this->repo_id)->once();

        $this->backend->disconnectFromGerrit($this->repository);
    }
}
