<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_ExecTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    private $fixture_dir;
    private $git_exec;
    private $symlink_repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->symlink_repo = $this->getTmpDir() . '/tuleap-git-exec-test_' . rand(0, 99999999);
        $this->fixture_dir = $this->getTmpDir() . '/tuleap-git-exec-test_' . rand(0, 99999999);
        mkdir($this->fixture_dir);
        symlink($this->fixture_dir, $this->symlink_repo);
        system("cd $this->fixture_dir && git init 2>&1 >/dev/null");

        $this->git_exec = new Git_Exec($this->fixture_dir);
        $this->git_exec->setLocalCommiter('test', 'test@example.com');
    }

    protected function tearDown(): void
    {
        system("rm -rf $this->fixture_dir");
        unlink($this->symlink_repo);

        parent::tearDown();
    }

    public function testThereIsSomethingToCommitWhenStuffIsAdded(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsSomethingToCommitWhenStuffIsRemoved(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        $this->git_exec->rm("$this->fixture_dir/toto");
        $this->assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsSomethingToCommitWhenStuffIsMoved(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        $this->git_exec->mv("$this->fixture_dir/toto", "$this->fixture_dir/tata");
        $this->assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testItMovesTheFilesInSymlinkedRepo(): void
    {
        $file_orig_path = $this->symlink_repo . '/file1';
        $file_dest_path = $this->symlink_repo . '/file with spaces';
        file_put_contents($file_orig_path, 'bla');
        $git_exec = new Git_Exec($this->symlink_repo);
        $git_exec->add($file_orig_path);
        $git_exec->commit('bla');
        $git_exec->mv($file_orig_path, $file_dest_path);
        $git_exec->commit('rename');
        $this->assertFalse($git_exec->isThereAnythingToCommit());
        $this->assertFalse(file_exists($file_orig_path));
        $this->assertEquals('bla', file_get_contents($file_dest_path));
    }

    public function testThereIsNothingToCommitOnEmptyRepository(): void
    {
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitOnAlreadyCommitedRepo(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitWhenNewFilesAreNotAdded(): void
    {
        touch("$this->fixture_dir/toto");
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitWhenContentDoesntChange(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testItDoesntRaiseAnErrorWhenTryingToRemoveAnUntrackedFile(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->rm("$this->fixture_dir/toto");

        //All is OK
        $this->assertTrue(true);
    }

    public function testItReturnsTrueWhenTheRevExists(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->doesObjectExists('master');

        $this->assertTrue($res);
    }

    public function testItReturnsFalseWhenTheRevDoesNotExist(): void
    {
        $res = $this->git_exec->doesObjectExists('this_is_not_a_rev');

        $this->assertFalse($res);
    }
}
