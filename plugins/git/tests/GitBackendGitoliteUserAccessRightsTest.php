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

use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;

require_once __DIR__.'/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitBackendGitoliteUserAccessRightsTest extends TuleapTestCase
{
    /**
     * @var Git_Backend_Gitolite
     */
    private $backend;

    public function setUp()
    {
        parent::setUp();
        $driver        = mock('Git_GitoliteDriver');
        $this->backend = new Git_Backend_Gitolite($driver, mock(GitoliteAccessURLGenerator::class), mock('Logger'));

        $this->user       = mock('PFUser');
        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(1);
        stub($this->repository)->getProjectId()->returns(101);
    }

    public function itReturnsTrueIfUserIsProjectAdmin()
    {
        stub($this->user)->isMember(101, 'A')->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasReadAccess()
    {
        stub($this->user)->hasPermission(Git::PERM_READ, 1, 101)->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasReadAccessAndRepositoryIsMigratedToGerrit()
    {
        stub($this->user)->hasPermission(Git::PERM_READ, 1, 101)->returns(true);
        stub($this->repository)->isMigratedToGerrit()->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasWriteAccess()
    {
        stub($this->user)->hasPermission(Git::PERM_WRITE, 1, 101)->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsFalseIfUserHasWriteAccessAndRepositoryIsMigratedToGerrit()
    {
        stub($this->user)->hasPermission(Git::PERM_WRITE, 1, 101)->returns(true);
        stub($this->repository)->isMigratedToGerrit()->returns(true);

        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsTrueIfUserHasRewindAccess()
    {
        stub($this->user)->hasPermission(Git::PERM_WPLUS, 1, 101)->returns(true);

        $this->assertTrue($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsFalseIfUserHasRewindAccessAndRepositoryIsMigratedToGerrit()
    {
        stub($this->user)->hasPermission(Git::PERM_WPLUS, 1, 101)->returns(true);
        stub($this->repository)->isMigratedToGerrit()->returns(true);

        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }

    public function itReturnsFalseIfUserHasNoPermissions()
    {
        $this->assertFalse($this->backend->userCanRead($this->user, $this->repository));
    }
}
