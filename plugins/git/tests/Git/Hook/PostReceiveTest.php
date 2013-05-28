<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class PostReceiveTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->git_exec_repo = mock('Git_Exec');
        $this->extract_cross_ref = mock('Git_Hook_ExtractCrossReferences');
        $this->post_receive = new Git_Hook_PostReceive($this->git_exec_repo, $this->extract_cross_ref);
    }

    public function itIteratesOnRevs() {
        expect($this->git_exec_repo)->revList('d8f1e57', '469eaa9')->once();
        stub($this->git_exec_repo)->revList()->returns(array());

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itIteratesOnRevsSinceStart() {
        expect($this->git_exec_repo)->revListSinceStart('refs/heads/master', '469eaa9')->once();
        stub($this->git_exec_repo)->revListSinceStart()->returns(array());

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', '0000000000000000000000000000000000000000', '469eaa9', 'refs/heads/master');
    }

    public function itDoesntAttemptToExtractWhenBranchIsDeleted() {
        expect($this->extract_cross_ref)->extract()->never();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', '469eaa9', '0000000000000000000000000000000000000000', 'refs/heads/master');
    }

    public function itGetsEachRevisionContent() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));

        expect($this->git_exec_repo)->catFile('469eaa92')->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itExtractCrossReferencesForGivenUser() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));
        stub($this->git_exec_repo)->catFile()->returns('whatever');

        expect($this->extract_cross_ref)->extract('*', 'john_doe', '*', '*', '*')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itExtractCrossReferencesOnGitCommit() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));
        stub($this->git_exec_repo)->catFile()->returns('whatever');

        expect($this->extract_cross_ref)->extract('*', '*', 'git_commit', '*', '*')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itExtractCrossReferencesOnCommitMessage() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));
        stub($this->git_exec_repo)->catFile()->returns('bla bla bla');

        expect($this->extract_cross_ref)->extract('*', '*', '*', '*', 'bla bla bla')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itSetTheReferenceToTheRepository() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));
        stub($this->git_exec_repo)->catFile()->returns('');

        expect($this->extract_cross_ref)->extract('*', '*', '*', 'dev/469eaa92', '*')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    public function itSetTheReferenceToTheRepositoryWithSubRepo() {
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));
        stub($this->git_exec_repo)->catFile()->returns('');

        expect($this->extract_cross_ref)->extract('*', '*', '*', 'arch/x86_64/dev/469eaa92', '*')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/arch/x86_64/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }
}

class PostReceive_ExtractProjectNameTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->git_exec_repo = mock('Git_Exec');
        stub($this->git_exec_repo)->revList()->returns(array('469eaa92'));
        stub($this->git_exec_repo)->catFile()->returns('whatever');
        $this->extract_cross_ref = mock('Git_Hook_ExtractCrossReferences');
        $this->post_receive = new Git_Hook_PostReceive($this->git_exec_repo, $this->extract_cross_ref);
    }

    public function itExtractCrossReferencesOnProject() {
        expect($this->extract_cross_ref)->extract('garden', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromProjectRepos() {
        expect($this->extract_cross_ref)->extract('myproject', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/gitroot/myproject/stuff.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromProjectRepos2() {
        expect($this->extract_cross_ref)->extract('gpig', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/gitolite/repositories/gpig/dalvik.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromProjectRepos3() {
        expect($this->extract_cross_ref)->extract('gpig', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/var/lib/codendi/gitolite/repositories/gpig/dalvik.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromProjectRepos4() {
        expect($this->extract_cross_ref)->extract('gpig', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/var/lib/codendi/gitroot/gpig/dalvik.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsTheNameAfterTheFirstOccurrenceOfRootPath() {
        expect($this->extract_cross_ref)->extract('gitroot', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/gitroot/gitroot/stuff.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromPersonalRepos() {
        expect($this->extract_cross_ref)->extract('gpig', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/var/lib/codendi/gitolite/repositories/gpig/u/manuel/dalvik.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromPersonalRepos2() {
        expect($this->extract_cross_ref)->extract('gpig', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/var/lib/codendi/gitroot/gpig/u/manuel/dalvik.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameFromSymlinkedRepo() {
        expect($this->extract_cross_ref)->extract('chene', '*', '*', '*', '*')->once();
        $this->post_receive->execute('/data/codendi/gitroot/chene/gitshell.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }

    function testExtractsGroupNameThrowsAnExceptionWhenNoProjectNameFound() {
        $this->expectException('GitNoProjectFoundException');
        expect($this->extract_cross_ref)->extract()->never();
        $this->post_receive->execute('/non_existing_path/dalvik.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master');
    }
}
?>
