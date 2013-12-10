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

class Git_Hook_ExtractCrossReferencesTest extends TuleapTestCase {
    private $git_exec_repo;
    private $user;
    private $repository;
    private $repository_in_subpath;
    private $reference_manager;
    private $post_receive;
    private $push_details;

    public function setUp() {
        parent::setUp();

        $project = stub('Project')->getID()->returns(101);

        $this->git_exec_repo = mock('Git_Exec');

        $this->repository = mock('GitRepository');
        stub($this->repository)->getFullName()->returns('dev');
        stub($this->repository)->getProject()->returns($project);

        $this->repository_in_subpath = mock('GitRepository');
        stub($this->repository_in_subpath)->getProject()->returns($project);
        stub($this->repository_in_subpath)->getFullName()->returns('arch/x86_64/dev');

        $this->user = aUser()->withId(350)->build();
        $this->reference_manager = mock('ReferenceManager');

        $this->post_receive = new Git_Hook_ExtractCrossReferences($this->git_exec_repo, $this->reference_manager);

        $this->push_details  = new Git_Hook_PushDetails($this->repository, $this->user, 'refs/heads/master', 'whatever', 'whatever', array());
    }


    public function itGetsEachRevisionContent() {
        expect($this->git_exec_repo)->catFile('469eaa9')->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function itExtractCrossReferencesForGivenUser() {
        stub($this->git_exec_repo)->catFile()->returns('whatever');

        expect($this->reference_manager)->extractCrossRef('*', '*', '*', '*', 350)->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function itExtractCrossReferencesOnGitCommit() {
        stub($this->git_exec_repo)->catFile()->returns('whatever');

        expect($this->reference_manager)->extractCrossRef('*', '*', Git::REFERENCE_NATURE, '*', '*')->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function itExtractCrossReferencesOnCommitMessage() {
        stub($this->git_exec_repo)->catFile()->returns('bla bla bla');

        expect($this->reference_manager)->extractCrossRef('bla bla bla', '*', '*', '*', '*')->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function itExtractCrossReferencesForProject() {
        stub($this->git_exec_repo)->catFile()->returns('');

        expect($this->reference_manager)->extractCrossRef('*', '*', '*', 101, '*')->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function itSetTheReferenceToTheRepository() {
        stub($this->git_exec_repo)->catFile()->returns('');

        expect($this->reference_manager)->extractCrossRef('*', 'dev/469eaa9', '*', '*', '*')->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function itSetTheReferenceToTheRepositoryWithSubRepo() {
        stub($this->git_exec_repo)->catFile()->returns('');

        expect($this->reference_manager)->extractCrossRef('*', 'arch/x86_64/dev/469eaa9', '*', '*', '*')->once();

        $push_details = new Git_Hook_PushDetails($this->repository_in_subpath, $this->user, 'refs/heads/master', 'whatever', 'whatever', array());
        $this->post_receive->execute($push_details, '469eaa9');
    }
}

?>
