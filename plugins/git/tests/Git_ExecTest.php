<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 */

require_once(dirname(__FILE__).'/../include/constants.php');
require_once dirname(__FILE__).'/../include/Git_Exec.class.php';

class Git_Exec_IsThereAnythingToCommitTest extends TuleapTestCase {
    private $fixture_dir;
    private $git_exec;

    public function setUp() {
        parent::setUp();

        $this->fixture_dir = '/tmp/tuleap-git-exec-test';
        mkdir($this->fixture_dir);
        system("cd $this->fixture_dir && git init 2>&1 >/dev/null");

        $this->git_exec = new Git_Exec($this->fixture_dir);
    }

    public function tearDown() {
        parent::tearDown();
        system("rm -rf $this->fixture_dir");
    }

    public function testThereIsSomethingToCommitWhenStuffIsAdded() {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsSomethingToCommitWhenStuffIsRemoved() {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        $this->git_exec->rm("$this->fixture_dir/toto");
        $this->assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsSomethingToCommitWhenStuffIsMoved() {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        $this->git_exec->mv("$this->fixture_dir/toto", "$this->fixture_dir/tata");
        $this->assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitOnEmptyRepository() {
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitOnAlreadyCommitedRepo() {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitWhenNewFilesAreNotAdded() {
        touch("$this->fixture_dir/toto");
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitWhenContentDoesntChange() {
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function itDoesntRaiseAnErrorWhenTryingToRemoveAnUntrackedFile() {
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->rm("$this->fixture_dir/toto");
    }
}

?>
