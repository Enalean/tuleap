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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

require_once __DIR__ . '/bootstrap.php';

class GitExecTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    private $fixture_dir;

    /**
     * @var GitExec
     */
    private $git_exec;

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
        $sha1 = $this->git_exec->getBranchSha1('refs/heads/master');

        $this->assertNotNull($sha1);
        $this->assertNotEmpty($sha1);
        $this->assertEquals(40, strlen($sha1));
    }

    public function testItThrowsAnExceptionIfBranchDoesNotExist(): void
    {
        $this->expectException('Tuleap\PullRequest\Exception\UnknownBranchNameException');

        $this->git_exec->getBranchSha1('refs/heads/universitylike');
    }

    public function testItReturnsAnArrayOfModifiedFiles(): void
    {
        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/toto", "jackassness");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("modify toto");

        $sha1_src  = $this->git_exec->getBranchSha1('refs/heads/dev');
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/master');

        $files = $this->git_exec->getModifiedFilesNameStatus($sha1_src, $sha1_dest);

        $this->assertTrue(count($files) > 0);
        $this->assertEquals('M	toto', $files[0]);
    }

    public function testItUsesTheCommonAncestorToDoTheDiff(): void
    {
        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");
        system("cd $this->fixture_dir && git checkout --quiet master 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/added-file-in-master", "whatever");
        $this->git_exec->add("$this->fixture_dir/added-file-in-master");
        $this->git_exec->commit("add file added-file-in-master");

        system("cd $this->fixture_dir && git checkout --quiet dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/added-file-in-dev", "jackassness");
        $this->git_exec->add("$this->fixture_dir/added-file-in-dev");
        $this->git_exec->commit("add file added-file-in-dev");

        $sha1_src  = $this->git_exec->getBranchSha1('refs/heads/dev');
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/master');

        $files = $this->git_exec->getModifiedFilesNameStatus($sha1_src, $sha1_dest);

        $this->assertEquals('A	added-file-in-dev', $files[0]);
    }

    public function testItThrowsAnExceptionIfReferenceIsUnknown(): void
    {
        $sha1_src  = 'weeakplbrhmj03pjcjtw5blestib2hy3rgpwxmwt';
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/master');

        $this->expectException('Tuleap\PullRequest\Exception\UnknownReferenceException');

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

        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");

        $this->git_exec->rm($file2_path);
        file_put_contents($file1_path, "Contenu\nContenu2");
        $this->git_exec->add($file1_path);
        $this->git_exec->commit("rm $file2_path & modify $file1_path");

        $short_stat = $this->git_exec->getShortStat('master', 'dev');
        $this->assertEquals(2, $short_stat->getFilesChangedNumber());
        $this->assertEquals(1, $short_stat->getLinesAddedNumber());
        $this->assertEquals(1, $short_stat->getLinesRemovedNumber());
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

        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");

        $this->git_exec->rm($file2_path);
        file_put_contents($file1_path, "Contenu\nContenu2");
        $this->git_exec->add($file1_path);
        $this->git_exec->commit("rm $file2_path & modify $file1_path");

        $modified_files = $this->git_exec->getModifiedFilesLineStat('master', 'dev');

        $this->assertEquals(["1\t0\tfile1", "0\t1\tfile2"], $modified_files);
    }

    public function testItReturnsFalseIfBaseCommitRefDoesntExist_isAncestor(): void // phpcs:ignore
    {
        $res = $this->git_exec->isAncestor('aldfkezjjzfrkj', 'HEAD');
        $this->assertFalse($res);
    }

    public function testItReturnsFalseIfMergedCommitRefDoesntExist_isAncestor(): void // phpcs:ignore
    {
        $res = $this->git_exec->isAncestor('HEAD^', 'fezefzefzeef');
        $this->assertFalse($res);
    }

    public function testItReturnsFalseWhenIsNotAncestor(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff2");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->isAncestor('HEAD^', 'HEAD');
        $this->assertFalse($res);
    }

    public function testItReturnsTrueWhenIsAncestor(): void
    {
        file_put_contents("$this->fixture_dir/toto", "stuff2");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->isAncestor('HEAD', 'HEAD^');
        $this->assertTrue($res);
    }
}
