<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

class Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepoTest extends TuleapTestCase
{

    private $fixtures;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->response = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->fixtures = $this->getTmpDir();
        copy(dirname(__FILE__) . '/_fixtures/gitolite_admin.tgz', $this->fixtures . '/gitolite_admin.tgz');

        $this->remote_admin_repository = 'gitolite_admin';

        `tar -xzf $this->fixtures/gitolite_admin.tgz --directory $this->fixtures`;
        `(cd $this->fixtures && git clone gitolite_admin admin)`;
        $this->expected_file_in_old_dir = md5(uniqid(rand(), true));
        touch($this->fixtures .'/admin/'. $this->expected_file_in_old_dir);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo(
            $this->response,
            $this->fixtures,
            $this->remote_admin_repository
        );
        $this->command->clearExecuteAs();
    }

    public function itAbortsIfThereIsAlreadyABackupDir()
    {
        $next = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        `(cd $this->fixtures && cp -r admin admin.old)`;
        $this->command->setNextCommand($next);

        $this->response->shouldReceive('error')->with("The gitolite backup dir $this->fixtures/admin.old already exists. Please remove it.")->once();
        $this->response->shouldReceive('abort')->once();
        $next->shouldReceive('execute')->never();

        $this->command->execute();
    }

    public function itMovesTheAdminDirInABackupDir()
    {
        $this->command->execute();

        $this->assertTrue(is_file($this->fixtures .'/admin.old/'. $this->expected_file_in_old_dir));
    }

    public function itClonesAFreshRepository()
    {
        $this->command->execute();

        $this->assertTrue(is_dir($this->fixtures .'/admin/'));
        $this->assertFalse(is_dir($this->fixtures .'/admin/'. $this->expected_file_in_old_dir));
    }

    public function itDisplaysMeaningfulFeedbackToTheUser()
    {
        $this->response->shouldReceive('info')->with("Moving admin to $this->fixtures/admin.old and cloning $this->remote_admin_repository")->once();

        $this->command->execute();
    }

    public function itExecutesTheNextCommand()
    {
        $next = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        $next->shouldReceive('execute')->once();

        $this->command->setNextCommand($next);

        $this->command->execute();
    }
}
