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

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_ReferenceManagerTest extends TuleapTestCase
{
    private $project;
    private $repository_factory;
    private $reference_manager;
    private $repository;
    private $git_reference_manager;

    public function setUp()
    {
        parent::setUp();
        $this->project               = mock('Project');
        stub($this->project)->getId()->returns(101);
        stub($this->project)->getUnixName()->returns('gpig');
        $this->repository_factory    = mock('GitRepositoryFactory');
        $this->reference_manager     = mock('ReferenceManager');
        $this->repository            = mock('GitRepository');
        stub($this->repository)->getId()->returns(222);
        $this->git_reference_manager = new Git_ReferenceManager($this->repository_factory, $this->reference_manager);
    }

    public function itLoadsTheRepositoryWhenRootRepo()
    {
        expect($this->repository_factory)->getRepositoryByPath(101, 'gpig/rantanplan.git')->once();
        $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
    }

    public function itLoadsTheRepositoryWhenRepoInHierarchy()
    {
        expect($this->repository_factory)->getRepositoryByPath(101, 'gpig/dev/x86_64/rantanplan.git')->once();
        $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'dev/x86_64/rantanplan/469eaa9');
    }

    public function itLoadTheReferenceFromDb()
    {
        stub($this->repository_factory)->getRepositoryByPath()->returns($this->repository);

        expect($this->reference_manager)->loadReferenceFromKeywordAndNumArgs(Git::REFERENCE_KEYWORD, 101, 2, 'rantanplan/469eaa9')->once();

        $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
    }

    public function itReturnsTheReference()
    {
        $reference = mock('Reference');
        stub($this->repository_factory)->getRepositoryByPath()->returns($this->repository);
        stub($this->reference_manager)->loadReferenceFromKeywordAndNumArgs()->returns($reference);

        $ref = $this->git_reference_manager->getReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
        $this->assertEqual($ref, $reference);
    }
}
