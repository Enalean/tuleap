<?php
/**
 * Copyright Enalean (c) 2016 - 2017. All rights reserved.
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


namespace Tuleap\SVN\Hooks;

use ForgeConfig;
use ProjectManager;
use SVN_CommitToTagDeniedException;
use Tuleap\SVN\Commit\CommitInfo;
use Tuleap\SVN\Commit\CommitInfoEnhancer;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class PreCommitBaseTest extends TuleapTestCase {

    private $commit_message;
    private $transaction;
    private $immutable_tag_factory;
    private $dao;
    private $repository_manager;
    private $project_id;
    private $repository;

    public function setUp() {
        $this->immutable_tag_factory = mock('Tuleap\SVN\Admin\ImmutableTagFactory');
        $this->dao                   = mock('Tuleap\SVN\Admin\ImmutableTagDao');
        $this->commit_info_enhancer  = mock('Tuleap\SVN\Commit\CommitInfoEnhancer');
        $this->repository_manager    = mock('Tuleap\SVN\Repository\RepositoryManager');

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
        $svn_look = safe_mock('Tuleap\SVN\Commit\SVNLook');
        stub($svn_look)->getMessageFromTransaction()->returns(array("COMMIT MSG"));
        stub($svn_look)->getTransactionPath()->returns($paths);

        $pre_commit = new PreCommit(
            $this->system_path,
            $this->transaction,
            $this->repository_manager,
            new CommitInfoEnhancer($svn_look, new CommitInfo()),
            $this->immutable_tag_factory,
            $svn_look,
            mock('Tuleap\\Svn\\SHA1CollisionDetector'),
            mock('BackendLogger'),
            mock('Tuleap\SVN\Repository\HookConfigRetriever')
        );
        $pre_commit->assertCommitToTagIsAllowed();
    }

    private function mockRepository() {
        $this->repository = mock('Tuleap\SVN\Repository\Repository');
        stub($this->repository)->getId()->returns(1);
        stub($this->repository)->getName()->returns($this->repository_name);
        stub($this->repository_manager)->getRepositoryFromSystemPath($this->system_path)->returns($this->repository);
     }

    public function testCommitToTagIsAllowed() {
        $immutable_tags = stub("Tuleap\SVN\Admin\ImmutableTag")->getPaths()->returns(array());

        stub($this->immutable_tag_factory)->getByRepositoryId()->returns($immutable_tags);

        $this->assertEqual($immutable_tags->getPaths(), array());

        $this->assertCommitIsAllowed('A   file');
        $this->assertCommitIsAllowed('U   file');
        $this->assertCommitIsAllowed('D   file');

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
        $immutable_tag = stub("Tuleap\SVN\Admin\ImmutableTag")->getPaths()->returns(array('/*/tags/'));
        stub("Tuleap\SVN\Admin\ImmutableTagDao")->searchByRepositoryId()->returns(array($this->repository));
        stub($this->immutable_tag_factory)->getByRepositoryId($this->repository)->returns($immutable_tag);

        $this->assertCommitIsDenied('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   file');
        $this->assertCommitIsAllowed('U   file');
        $this->assertCommitIsAllowed('D   file');

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

    public function testCommitToTagIsDeniedAtRootAndInModules()
    {
        $immutable_tag = stub("Tuleap\SVN\Admin\ImmutableTag")->getPaths()->returns(array('tags', '/*/tags'));
        stub("Tuleap\SVN\Admin\ImmutableTagDao")->searchByRepositoryId()->returns(array($this->repository));
        stub($this->immutable_tag_factory)->getByRepositoryId($this->repository)->returns($immutable_tag);

        $this->assertCommitIsAllowed('A   file');
        $this->assertCommitIsAllowed('U   file');
        $this->assertCommitIsAllowed('D   file');

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

class PreCommitReferenceTest extends TuleapTestCase
{
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;

    /** @var string repository path */
    private $repo_path;

    /** @var string transaction */
    private $transaction;

    /** @var Tuleap\SVN\Repository\Repository */
    private $repo;

    /** @var Tuleap\SVN\Repository\RepositoryManager */
    private $repo_manager;

    /** @var Tuleap\SVN\Repository\HookConfig */
    private $hook_config;

    /** @var Tuleap\SVN\Commit\SVNLook */
    private $svnlook;

    /** @var PreCommit */
    private $hook;

    public function setUp()
    {
        global $Language;
        parent::setUp();

        ForgeConfig::store();
        ForgeConfig::set('sys_data_dir', parent::getTmpDir());

        $this->repo         = safe_mock('Tuleap\SVN\Repository\Repository');
        $this->svnlook      = safe_mock('Tuleap\SVN\Commit\SVNLook');
        $this->repo_manager = safe_mock('Tuleap\SVN\Repository\RepositoryManager');
        $this->hook_config  = safe_mock('Tuleap\SVN\Repository\HookConfig');
        $this->repo_path    = "FOO";
        $this->transaction  = "BAR";

        $this->hook_config_retriever = mock('Tuleap\SVN\Repository\HookConfigRetriever');
        $this->reference_manager     = mock('ReferenceManager');

        stub($this->repo_manager)->getRepositoryFromSystemPath()->returns($this->repo);
        stub($this->hook_config_retriever)->getHookConfig()->returns($this->hook_config);

        $Language = mock('BaseLanguage');
    }

    public function tearDown() {
        global $Language;
        unset($Language);
        ForgeConfig::restore();
        ProjectManager::clearInstance();
        parent::tearDown();
    }

    private function preCommit(){
        $this->hook = new PreCommit(
            $this->repo_path,
            $this->transaction,
            $this->repo_manager,
            new CommitInfoEnhancer($this->svnlook, new CommitInfo()),
            safe_mock('Tuleap\SVN\Admin\ImmutableTagFactory'),
            $this->svnlook,
            mock('Tuleap\\Svn\\SHA1CollisionDetector'),
            safe_mock('Logger'),
            $this->hook_config_retriever
        );
    }

    public function itRejectsCommitIfCommitMessageIsEmptyAndForgeRequiresACommitMessage()
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', false);
        stub($this->svnlook)->getMessageFromTransaction()->atLeastOnce()->returns(array(""));
        stub($this->hook_config)->getHookConfig(HookConfig::MANDATORY_REFERENCE)->returns(false);

        $this->preCommit();

        $this->expectException('Exception');
        $this->hook->assertCommitMessageIsValid(safe_mock('ReferenceManager'));
    }

    public function itDoesNotRejectCommitIfCommitMessageIsEmptyAndForgeDoesNotRequireACommitMessage()
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', true);
        stub($this->svnlook)->getMessageFromTransaction()->returns(array(""));
        stub($this->hook_config)->getHookConfig(HookConfig::MANDATORY_REFERENCE)->returns(false);

        $this->preCommit();

        $this->hook->assertCommitMessageIsValid($this->reference_manager);
    }

    public function itRejectsCommitMessagesWithoutArtifactReference()
    {
        $project = safe_mock('Project');

        stub($this->svnlook)->getMessageFromTransaction()->atLeastOnce()->returns(array("Commit message witout reference"));
        stub($this->hook_config)->getHookConfig(HookConfig::MANDATORY_REFERENCE)->once()->returns(true);
        stub($this->repo)->getProject()->once()->returns($project);
        stub($project)->getId()->returns(123);
        stub($this->reference_manager)->stringContainsReferences("Commit message witout reference", '*')->once( )->returns(false);

        $this->preCommit();

        $this->expectException('Exception');
        $this->hook->assertCommitMessageIsValid($this->reference_manager);
    }
}
