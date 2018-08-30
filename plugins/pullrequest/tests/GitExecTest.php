<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use TuleapTestCase;

require_once 'bootstrap.php';

class GitExecTest extends TuleapTestCase
{
    private $fixture_dir;

    /**
     * @var GitExec
     */
    private $git_exec;

    public function setUp()
    {
        parent::setUp();

        $this->fixture_dir = '/tmp/tuleap-pullrequest-git-exec-test_'.rand(0, 99999999);
        mkdir($this->fixture_dir);
        system("cd $this->fixture_dir && git init 2>&1 >/dev/null");

        $this->git_exec = new GitExec($this->fixture_dir);
        $this->git_exec->setLocalCommiter('John Doe', 'john.doe@example.com');

        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
    }

    public function tearDown()
    {
        system("rm -rf $this->fixture_dir");

        parent::tearDown();
    }

    public function itReturnsTheSha1OfAGivenBranch()
    {
        $sha1 = $this->git_exec->getBranchSha1('refs/heads/master');

        $this->assertNotNull($sha1);
        $this->assertNotEmpty($sha1);
        $this->assertEqual(strlen($sha1), 40);
    }

    public function itThrowsAnExceptionIfBranchDoesNotExist()
    {
        $this->expectException('Tuleap\PullRequest\Exception\UnknownBranchNameException');

        $sha1 = $this->git_exec->getBranchSha1('refs/heads/universitylike');
    }

    public function itReturnsAnArrayOfModifiedFiles()
    {
        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/toto", "jackassness");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("modify toto");

        $sha1_src  = $this->git_exec->getBranchSha1('refs/heads/dev');
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/master');

        $files = $this->git_exec->getModifiedFiles($sha1_src, $sha1_dest);

        $this->assertArrayNotEmpty($files);
        $this->assertEqual($files[0], 'M	toto');
    }

    public function itUsesTheCommonAncestorToDoTheDiff()
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

        $files = $this->git_exec->getModifiedFiles($sha1_src, $sha1_dest);

        $this->assertEqual($files[0], 'A	added-file-in-dev');
    }

    public function itThrowsAnExceptionIfReferenceIsUnknown()
    {
        $sha1_src  = 'weeakplbrhmj03pjcjtw5blestib2hy3rgpwxmwt';
        $sha1_dest = $this->git_exec->getBranchSha1('refs/heads/master');

        $this->expectException('Tuleap\PullRequest\Exception\UnknownReferenceException');

        $this->git_exec->getModifiedFiles($sha1_src, $sha1_dest);
    }

    public function itReturnsShortStat()
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
        $this->assertEqual(2, $short_stat->getFilesChangedNumber());
        $this->assertEqual(1, $short_stat->getLinesAddedNumber());
        $this->assertEqual(1, $short_stat->getLinesRemovedNumber());
    }

    public function itReturnsShortStatForOneFile()
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

        $short_stat = $this->git_exec->getFileDiffStat('master', 'dev', $file1_path);
        $this->assertEqual(1, $short_stat->getFilesChangedNumber());
        $this->assertEqual(1, $short_stat->getLinesAddedNumber());
        $this->assertEqual(0, $short_stat->getLinesRemovedNumber());

        $short_stat = $this->git_exec->getFileDiffStat('master', 'dev', $file2_path);
        $this->assertEqual(1, $short_stat->getFilesChangedNumber());
        $this->assertEqual(0, $short_stat->getLinesAddedNumber());
        $this->assertEqual(1, $short_stat->getLinesRemovedNumber());
    }

    public function itReturnsFalseIfBaseCommitRefDoesntExist_isAncestor() // phpcs:ignore
    {
        $res = $this->git_exec->isAncestor('aldfkezjjzfrkj', 'HEAD');
        $this->assertEqual(false, $res);
    }

    public function itReturnsFalseIfMergedCommitRefDoesntExist_isAncestor() // phpcs:ignore
    {
        $res = $this->git_exec->isAncestor('HEAD^', 'fezefzefzeef');
        $this->assertEqual(false, $res);
    }

    public function itReturnsFalseWhenIsNotAncestor()
    {
        file_put_contents("$this->fixture_dir/toto", "stuff2");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->isAncestor('HEAD^', 'HEAD');
        $this->assertEqual(false, $res);
    }

    public function itReturnsTrueWhenIsAncestor()
    {
        file_put_contents("$this->fixture_dir/toto", "stuff2");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");

        $res = $this->git_exec->isAncestor('HEAD', 'HEAD^');
        $this->assertEqual(true, $res);
    }
}
