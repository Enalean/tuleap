<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Git_Command_Exception;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\TemporaryTestDirectory;

final class GitExecTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private string $fixture_dir;
    private GitExec $git_exec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture_dir = $this->getTmpDir();

        $this->git_exec = new GitExec($this->fixture_dir);
        $this->git_exec->init();
        $this->git_exec->setLocalCommiter('John Doe', 'john.doe@example.com');

        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
    }

    public function testItReturnsTheSha1OfAGivenBranch(): void
    {
        $sha1 = $this->git_exec->getBranchSha1('refs/heads/main');

        self::assertNotNull($sha1);
        self::assertNotEmpty($sha1);
        self::assertEquals(40, strlen($sha1));
    }

    public function testItThrowsAnExceptionIfBranchDoesNotExist(): void
    {
        $this->expectException(UnknownBranchNameException::class);

        $this->git_exec->getBranchSha1('refs/heads/universitylike');
    }

    public function testItReturnsAnArrayOfModifiedFiles(): void
    {
        system("cd $this->fixture_dir && " . GitExec::getGitCommand() . " checkout --quiet -b dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/toto", "jackassness");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("modify toto");

        $sha1_src  = $this->git_exec->getBranchSha1('refs/heads/dev');
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/main');

        $files = $this->git_exec->getModifiedFilesNameStatus($sha1_src, $sha1_dest);

        self::assertTrue(count($files) > 0);
        self::assertEquals('M	toto', $files[0]);
    }

    public function testItUsesTheCommonAncestorToDoTheDiff(): void
    {
        system("cd $this->fixture_dir && " . GitExec::getGitCommand() . " checkout --quiet -b dev 2>&1 >/dev/null");
        system("cd $this->fixture_dir && " . GitExec::getGitCommand() . " checkout --quiet main 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/added-file-in-master", "whatever");
        $this->git_exec->add("$this->fixture_dir/added-file-in-master");
        $this->git_exec->commit("add file added-file-in-master");

        system("cd $this->fixture_dir && " . GitExec::getGitCommand() . " checkout --quiet dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/added-file-in-dev", "jackassness");
        $this->git_exec->add("$this->fixture_dir/added-file-in-dev");
        $this->git_exec->commit("add file added-file-in-dev");

        $sha1_src  = $this->git_exec->getBranchSha1('refs/heads/dev');
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/main');

        $files = $this->git_exec->getModifiedFilesNameStatus($sha1_src, $sha1_dest);

        self::assertEquals('A	added-file-in-dev', $files[0]);
    }

    public function testItThrowsAnExceptionIfTheCommandFailsToRetrieveModifiedLinesWithANonExistingBranch(): void
    {
        $sha1_src  = 'weeakplbrhmj03pjcjtw5blestib2hy3rgpwxmwt';
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/main');

        $this->expectException(Git_Command_Exception::class);

        $this->git_exec->getModifiedFilesNameStatus($sha1_src, $sha1_dest);
    }

    public function testItReturnsShortStat(): void
    {
        $file1_path = "$this->fixture_dir/file1";
        $file2_path = "$this->fixture_dir/file2";

        file_put_contents($file1_path, "Contenu\n");
        $this->git_exec->add($file1_path);
        file_put_contents($file2_path, "Contenu\n");
        $this->git_exec->add($file2_path);
        $this->git_exec->commit("add $file1_path & add $file2_path");

        system("cd $this->fixture_dir && " . GitExec::getGitCommand() . " checkout --quiet -b dev 2>&1 >/dev/null");

        $this->git_exec->rm($file2_path);
        file_put_contents($file1_path, "Contenu\nContenu2");
        $this->git_exec->add($file1_path);
        $this->git_exec->commit("rm $file2_path & modify $file1_path");

        $short_stat = $this->git_exec->getShortStat('main', 'dev');
        self::assertEquals(2, $short_stat->getFilesChangedNumber());
        self::assertEquals(1, $short_stat->getLinesAddedNumber());
        self::assertEquals(1, $short_stat->getLinesRemovedNumber());
    }

    public function testItReturnsModifiedLinesStats(): void
    {
        $file1_path = "$this->fixture_dir/file1";
        $file2_path = "$this->fixture_dir/file2";

        file_put_contents($file1_path, "Contenu\n");
        $this->git_exec->add($file1_path);
        file_put_contents($file2_path, "Contenu\n");
        $this->git_exec->add($file2_path);
        $this->git_exec->commit("add $file1_path & add $file2_path");

        system("cd $this->fixture_dir && " . GitExec::getGitCommand() . " checkout --quiet -b dev 2>&1 >/dev/null");

        $this->git_exec->rm($file2_path);
        file_put_contents($file1_path, "Contenu\nContenu2");
        $this->git_exec->add($file1_path);
        $this->git_exec->commit("rm $file2_path & modify $file1_path");

        $modified_files = $this->git_exec->getModifiedFilesLineStat('main', 'dev');

        self::assertEquals(["1\t0\tfile1", "0\t1\tfile2"], $modified_files);
    }

    public function testItReturnsFalseIfBaseCommitRefDoesntExist_isAncestor(): void // phpcs:ignore
    {
        $res = $this->git_exec->isAncestor('aldfkezjjzfrkj', 'HEAD');
        self::assertFalse($res);
    }

    public function testItReturnsFalseIfMergedCommitRefDoesntExist_isAncestor(): void // phpcs:ignore
    {
        $res = $this->git_exec->isAncestor('HEAD^', 'fezefzefzeef');
        self::assertFalse($res);
    }

    public function testItReturnsFalseWhenIsNotAncestor(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff2");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->isAncestor('HEAD^', 'HEAD');
        self::assertFalse($res);
    }

    public function testItReturnsTrueWhenIsAncestor(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff2");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->isAncestor('HEAD', 'HEAD^');
        self::assertTrue($res);
    }
}
