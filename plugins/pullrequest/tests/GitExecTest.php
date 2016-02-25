<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class GitExecTest extends TuleapTestCase {
    private $fixture_dir;

    /**
     * @var GitExec
     */
    private $git_exec;

    public function setUp() {
        parent::setUp();

        $this->fixture_dir = '/tmp/tuleap-pullrequest-git-exec-test_'.rand(0, 99999999);
        mkdir($this->fixture_dir);
        system("cd $this->fixture_dir && git init 2>&1 >/dev/null");

        $this->git_exec = new GitExec($this->fixture_dir);

        file_put_contents("$this->fixture_dir/toto", "stuff");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add stuff");
    }

    public function tearDown() {
        system("rm -rf $this->fixture_dir");

        parent::tearDown();
    }

    public function itReturnsTheSha1OfAGivenBranch() {
        $sha1 = $this->git_exec->getReferenceBranch('master');

        $this->assertNotNull($sha1);
        $this->assertNotEmpty($sha1);
        $this->assertEqual(strlen($sha1), 40);
    }

    public function itThrowsAnExceptionIfBranchDoesNotExist() {
        $this->expectException('Tuleap\PullRequest\Exception\UnknownBranchNameException');

        $sha1 = $this->git_exec->getReferenceBranch('universitylike');
    }

    public function itReturnsAnArrayOfModifiedFiles() {
        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/toto", "jackassness");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("modify toto");

        $sha1_src  = $this->git_exec->getReferenceBranch('dev');
        $sha1_dest = $this->git_exec->getReferenceBranch('master');

        $files = $this->git_exec->getModifiedFiles($sha1_src, $sha1_dest);

        $this->assertArrayNotEmpty($files);
        $this->assertEqual($files[0], 'M	toto');
    }

    public function itThrowsAnExceptionIfReferenceIsUnknown() {
        $sha1_src  = 'weeakplbrhmj03pjcjtw5blestib2hy3rgpwxmwt';
        $sha1_dest = $this->git_exec->getReferenceBranch('master');

        $this->expectException('Tuleap\PullRequest\Exception\UnknownReferenceException');

        $this->git_exec->getModifiedFiles($sha1_src, $sha1_dest);
    }

    public function itFetchesARemoteBranch() {
        $remote      = $this->fixture_dir;
        $branch_name = 'master';

        $result = $this->git_exec->fetch($remote, $branch_name);

        $this->assertTrue($result);
    }

    public function itThrowsWhileFetchingAnExceptionIfRemoteBranchDoesNotExist() {
        $remote      = $this->fixture_dir;
        $branch_name = 'morigeration';

        $this->expectException('Git_Command_Exception');

        $this->git_exec->fetch($remote, $branch_name);
    }

    public function itFetchesARemoteBranchWithoutHistory() {
        $remote      = $this->fixture_dir;
        $branch_name = 'master';

        $result = $this->git_exec->fetchNoHistory($remote, $branch_name);

        $this->assertTrue($result);
    }

    public function itThrowsWhileFetchingWithoutHistoryAnExceptionIfRemoteBranchDoesNotExist() {
        $remote      = $this->fixture_dir;
        $branch_name = 'rond';

        $this->expectException('Git_Command_Exception');

        $this->git_exec->fetchNoHistory($remote, $branch_name);
    }

    public function itMerges() {
        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/nonprophetic", "jackassness");
        $this->git_exec->add("$this->fixture_dir/nonprophetic");
        $this->git_exec->commit("add nonprophetic");
        $this->git_exec->checkoutBranch('master');

        $result = $this->git_exec->fastForwardMerge('dev');

        $this->assertTrue($result);
    }

    public function itThrowsWhileMergingABranchThatDoesNotExist() {
        $this->expectException('Git_Command_Exception');

        $this->git_exec->fastForwardMerge('dev');
    }

    public function itThrowsWhileMergingANonFastForwardChange() {
        system("cd $this->fixture_dir && git checkout --quiet -b dev 2>&1 >/dev/null");
        file_put_contents("$this->fixture_dir/nonprophetic", "jackassness");
        $this->git_exec->add("$this->fixture_dir/nonprophetic");
        $this->git_exec->commit("add nonprophetic");

        $this->git_exec->checkoutBranch('master');
        file_put_contents("$this->fixture_dir/toto", "baldly");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit("add toto");

        $this->expectException('Git_Command_Exception');

        $this->git_exec->fastForwardMerge('dev');
    }
}