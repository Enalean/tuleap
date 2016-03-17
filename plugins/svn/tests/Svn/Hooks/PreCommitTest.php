<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
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


namespace Tuleap\Svn\Hooks;

use TuleapTestCase;
use Tuleap\Svn\Hooks\PreCommit;
use SVN_CommitToTagDeniedException;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Admin\ImmutableTagDao;
use Tuleap\Svn\Admin\ImmutableTag;
use Tuleap\Svn\Commit\CommitInfoEnhancer;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\CannotFindRepositoryException;
use BackendLogger;

use Project;
use ProjectManager;

require_once __DIR__ .'/../../bootstrap.php';

class PreCommitBaseTest extends TuleapTestCase {

    private $repo;
    private $commit_message;
    private $transaction;
    private $immutable_tag_factory;
    private $dao;
    private $repository_manager;
    private $project_id;
    private $repository;

    public function setUp() {
        $this->immutable_tag_factory = mock('Tuleap\Svn\Admin\ImmutableTagFactory');
        $this->dao                   = mock('Tuleap\Svn\Admin\ImmutableTagDao');
        $this->commit_info_enhancer  = mock('Tuleap\Svn\Commit\CommitInfoEnhancer');
        $this->repository_manager    = mock('Tuleap\Svn\Repository\RepositoryManager');

        $this->repository_name = 'repositoryname';
        $this->commit_message  = '';
        $this->transaction     = '1';
        $this->project_id      = '1';
        $this->system_path     = $this->project_id . "/". $this->repository_name;

        $this->mockRepository();
    }

    private function assertCommitIsAllowed() {
        $paths = func_get_args();
        try {
            $this->preCommit($paths);
            $this->pass();
        } catch (SVN_CommitToTagDeniedException $ex) {
            $this->fail('Commit of "'.implode(', ', $paths) .'" should be allowed');
        }
    }

    private function assertCommitIsDenied() {
        $paths = func_get_args();
        try {
            $this->preCommit($paths);
            $this->fail('Commit of "'.implode(', ', $paths).'" should be denied');
        } catch (SVN_CommitToTagDeniedException $ex) {
            $this->pass();
        }
    }

    private function preCommit(array $paths) {
        $this->mockCommitInfo($paths);
        $pre_commit = new PreCommit(
            $this->immutable_tag_factory,
            $this->repository_manager,
            $this->commit_info_enhancer,
            mock('BackendLogger')
        );
        $pre_commit->assertCommitToTagIsAllowed(
           $this->system_path,
           $this->transaction
        );
    }

    private function mockRepository() {
        $this->repository = mock('Tuleap\Svn\Repository\Repository');
        stub($this->repository)->getId()->returns(1);
        stub($this->repository)->getName()->returns($this->repository_name);
        stub($this->repository_manager)->getRepositoryFromSystemPath($this->system_path)->returns($this->repository);
     }

     private function mockCommitInfo($path) {
        $this->commit_info_enhancer  = mock('Tuleap\Svn\Commit\CommitInfoEnhancer');
        $commit_info                 = stub("Tuleap\Svn\Commit\CommitInfo")->getTransactionPath()->returns($path);
        stub($this->commit_info_enhancer)->getCommitInfo()->returns($commit_info);
     }

    public function testCommitToTagIsAllowed() {
        $immutable_tags = stub("Tuleap\Svn\Admin\ImmutableTag")->getPaths()->returns("");
        $this->assertEqual($immutable_tags->getPaths(), "");

        $this->assertCommitIsAllowed('A   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('U   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('D   moduleA/trunk/toto');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/');
        $this->assertCommitIsAllowed('U   moduleA/tags/v1/');
        $this->assertCommitIsAllowed('D   moduleA/tags/v1/');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/toto');
        $this->assertCommitIsAllowed('U   moduleA/tags/v1/toto');
        $this->assertCommitIsAllowed('D   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto');
        $this->assertCommitIsAllowed('U   trunk/toto');
        $this->assertCommitIsAllowed('D   trunk/toto');

        $this->assertCommitIsAllowed('A   tags/v1/');
        $this->assertCommitIsAllowed('U   tags/v1/');
        $this->assertCommitIsAllowed('D   tags/v1/');

        $this->assertCommitIsAllowed('A   tags/v1/toto');
        $this->assertCommitIsAllowed('U   tags/v1/toto');
        $this->assertCommitIsAllowed('D   tags/v1/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/');
        $this->assertCommitIsAllowed('U   tags/moduleA/');
        $this->assertCommitIsAllowed('D   tags/moduleA/');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/');

        $this->assertCommitIsAllowed('A   tags/moduleA/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto', 'A   tags/moduleA/v1/toto');
    }

    public function testCommitToTagIsDeniedInModule() {
        $immutable_tag = stub("Tuleap\Svn\Admin\ImmutableTag")->getPaths()->returns('/*/tags');
        stub("Tuleap\Svn\Admin\ImmutableTagDao")->searchByRepositoryId()->returns(array($this->repository));
        stub($this->immutable_tag_factory)->getByRepositoryId($this->repository)->returns($immutable_tag);

        $this->assertCommitIsDenied('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('U   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('D   moduleA/trunk/toto');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/');

        $this->assertCommitIsDenied('A   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/toto');


        $this->assertCommitIsAllowed('A   trunk/toto');
        $this->assertCommitIsAllowed('U   trunk/toto');
        $this->assertCommitIsAllowed('D   trunk/toto');

        $this->assertCommitIsAllowed('A   tags/v1/');
        $this->assertCommitIsAllowed('U   tags/v1/');
        $this->assertCommitIsAllowed('D   tags/v1/');

        $this->assertCommitIsAllowed('A   tags/v1/toto');
        $this->assertCommitIsAllowed('U   tags/v1/toto');
        $this->assertCommitIsAllowed('D   tags/v1/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/');
        $this->assertCommitIsAllowed('U   tags/moduleA/');
        $this->assertCommitIsAllowed('D   tags/moduleA/');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/');

        $this->assertCommitIsAllowed('A   tags/moduleA/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto', 'A   tags/moduleA/v1/toto');
    }

    public function testCommitToTagIsDeniedAtRootAndInModules() {
        $paths = <<<EOS
/tags
/*/tags
EOS;

        $immutable_tag = stub("Tuleap\Svn\Admin\ImmutableTag")->getPaths()->returns($paths);
        stub("Tuleap\Svn\Admin\ImmutableTagDao")->searchByRepositoryId()->returns(array($this->repository));
        stub($this->immutable_tag_factory)->getByRepositoryId($this->repository)->returns($immutable_tag);

        $this->assertCommitIsAllowed('A   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('U   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('D   moduleA/trunk/toto');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/');

        $this->assertCommitIsDenied('A   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/toto');

        $this->assertCommitIsDenied('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto');
        $this->assertCommitIsAllowed('U   trunk/toto');
        $this->assertCommitIsAllowed('D   trunk/toto');

        $this->assertCommitIsAllowed('A   tags/v1/');
        $this->assertCommitIsDenied('U   tags/v1/');
        $this->assertCommitIsDenied('D   tags/v1/');

        $this->assertCommitIsDenied('A   tags/v1/toto');
        $this->assertCommitIsDenied('U   tags/v1/toto');
        $this->assertCommitIsDenied('D   tags/v1/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/');
        $this->assertCommitIsDenied('U   tags/moduleA/');
        $this->assertCommitIsDenied('D   tags/moduleA/');

        $this->assertCommitIsDenied('A   tags/moduleA/v1/');
        $this->assertCommitIsDenied('U   tags/moduleA/v1/');
        $this->assertCommitIsDenied('D   tags/moduleA/v1/');

        $this->assertCommitIsDenied('A   tags/moduleA/toto');
        $this->assertCommitIsDenied('U   tags/moduleA/toto');
        $this->assertCommitIsDenied('D   tags/moduleA/toto');

        $this->assertCommitIsDenied('A   tags/moduleA/v1/toto');
        $this->assertCommitIsDenied('U   tags/moduleA/v1/toto');
        $this->assertCommitIsDenied('D   tags/moduleA/v1/toto');

        $this->assertCommitIsDenied('A   trunk/toto', 'A   tags/moduleA/v1/toto');
    }
}