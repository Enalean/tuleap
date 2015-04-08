<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
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

/**
 * I'm responsible of handling what happens in pre-commit subversion hook
 */
class SVN_Hook_PreCommitTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->repo           = 'SVN_repo';
        $this->commit_message = '';
        $this->transaction    = '1';

        $svn_hook                       = stub('SVN_Hooks')->getProjectFromRepositoryPath($this->repo)->returns('');
        $this->commit_message_validator = mock('SVN_CommitMessageValidator');

        $this->pre_commit = new SVN_Hook_PreCommit(
            $svn_hook,
            $this->commit_message_validator
        );
    }

    public function itRejectsCommitIfCommitMessageIsEmptyAndForgeRequiresACommitMessage() {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', false);

        $this->expectException('Exception');
        expect($this->commit_message_validator)->assertCommitMessageIsValid()->never();

        $this->pre_commit->assertCommitMessageIsValid($this->repo, $this->commit_message);
    }

    public function itDoesNotRejectCommitIfCommitMessageIsEmptyAndForgeDoesNotRequireACommitMessage() {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', true);

        expect($this->commit_message_validator)->assertCommitMessageIsValid()->once();

        $this->pre_commit->assertCommitMessageIsValid($this->repo, $this->commit_message);
    }

    public function itDoesRejectCommitToTagIfCommitToTagNotAllowed() {
        $project = mock('Project');
        stub($project)->isCommitToTagDenied()->returns(true);
        $hook = partial_mock('SVN_Hook_PreCommit', array('getProjectFromRepositoryPath', 'assertItIsAllowedOperationToTag'));
        stub($hook)->getProjectFromRepositoryPath()->returns($project);
        stub($hook)->assertItIsAllowedOperationToTag()->returns(true);
        $this->expectException('Exception');
        $hook->assertCommitToTagIsAllowed($this->repo, $this->transaction);
    }

    public function itAssertsUpdatedTargetIsTag() {
        $path = array('U   project1/tags/release_1/');
        $this->assertTrue($this->pre_commit->isItUpdateOrDeleteToTag($path[0]));
    }

    public function itAssertsAddedTargetIsTag() {
        $path = array('A   project1/tags/release_1/');
        $this->assertFalse($this->pre_commit->isItUpdateOrDeleteToTag($path[0]));
    }
}