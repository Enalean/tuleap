<?php
/**
 * Copyright Enalean (c) 2016 - present. All rights reserved.
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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReferenceManager;
use SVN_CommitToTagDeniedException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\CommitMessageWithoutReferenceException;
use Tuleap\SVN\Commit\EmptyCommitMessageException;
use Tuleap\SVN\Commit\ImmutableTagCommitValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class PreCommitTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Repository
     */
    private $repository;
    private string $system_path;
    private string $repository_name;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\SVN\Repository\RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\SVN\Admin\ImmutableTagFactory
     */
    private $immutable_tag_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->immutable_tag_factory = $this->createMock(\Tuleap\SVN\Admin\ImmutableTagFactory::class);
        $this->repository_manager    = $this->createMock(\Tuleap\SVN\Repository\RepositoryManager::class);

        $this->repository_name = 'repositoryname';
        $project_id            = 1;
        $this->system_path     = $project_id . "/" . $this->repository_name;

        $this->repository = $this->createMock(\Tuleap\SVN\Repository\Repository::class);
        $this->repository->method('getId')->willReturn(1);
        $this->repository->method('getName')->willReturn($this->repository_name);
        $this->repository_manager->method('getRepositoryFromSystemPath')
            ->with($this->system_path)
            ->willReturn($this->repository);
    }

    private function assertCommitIsAllowed(): void
    {
        $paths = func_get_args();
        try {
            $this->preCommitToTags($paths);
            self::assertTrue(true); //This simulates that the test is OK
        } catch (SVN_CommitToTagDeniedException $ex) {
            self::fail('Commit of "' . implode(', ', $paths) . '" should be allowed');
        }
    }

    private function assertCommitIsDenied(): void
    {
        $paths = func_get_args();
        try {
            $this->preCommitToTags($paths);
            $this->fail('Commit of "' . implode(', ', $paths) . '" should be denied');
        } catch (SVN_CommitToTagDeniedException $ex) {
            self::assertTrue(true); //This simulates that the test is OK
        }
    }

    /**
     * @throws SVN_CommitToTagDeniedException
     */
    private function preCommitToTags(array $paths): void
    {
        $svn_look = $this->createMock(Svnlook::class);
        $svn_look->method('getMessageFromTransaction')->willReturn(["COMMIT MSG"]);
        $svn_look->method('getTransactionPath')->willReturn($paths);
        $svn_look->method('getContent');
        $svn_look->method('closeContentResource');

        $commit_message_validator = $this->createMock(CommitMessageValidator::class);
        $commit_message_validator->method('assertCommitMessageIsValid');

        $pre_commit = new PreCommit(
            $svn_look,
            new NullLogger(),
            $commit_message_validator,
            new ImmutableTagCommitValidator(
                new NullLogger(),
                $this->immutable_tag_factory,
            )
        );
        $pre_commit->assertCommitIsValid($this->repository, '1');
    }

    public function testCommitToWhiteListedTagIsAllowed(): void
    {
        $immutable_tags = $this->createMock(ImmutableTag::class);

        $immutable_tags->method('getPaths')->willReturn(['/*/tags/']);
        $immutable_tags->method('getWhitelist')->willReturn(["trunk/tags/v1/to to/"]);

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn($immutable_tags);

        $this->assertCommitIsAllowed('A   trunk/tags/v1/to to/banana');
        $this->assertCommitIsDenied('A   trunk/tags/v2/to to/banana');
    }

    public function testCommitToTagIsAllowed(): void
    {
        $immutable_tags = $this->createMock(ImmutableTag::class);
        $immutable_tags->method('getPaths')->willReturn([]);

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn($immutable_tags);

        self::assertEquals($immutable_tags->getPaths(), []);

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

    public function testCommitToTagIsDeniedInModule(): void
    {
        $immutable_tags = $this->createMock(ImmutableTag::class);
        $immutable_tags->method('getPaths')->willReturn(['/*/tags/']);
        $immutable_tags->method('getWhitelist')->willReturn([]);

        $immutable_tag_dao = $this->createMock(ImmutableTagDao::class);
        $immutable_tag_dao->method('searchByRepositoryId')->willReturn([$this->repository]);

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn($immutable_tags);

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

    public function testCommitToTagIsDeniedAtRootAndInModules(): void
    {
        $immutable_tags = $this->createMock(ImmutableTag::class);
        $immutable_tags->method('getPaths')->willReturn(['tags', '/*/tags']);
        $immutable_tags->method('getWhitelist')->willReturn([]);

        $immutable_tag_dao = $this->createMock(ImmutableTagDao::class);
        $immutable_tag_dao->method('searchByRepositoryId')->willReturn([$this->repository]);

        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn($immutable_tags);

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

    public function testItRejectsCommitIfCommitMessageIsEmptyAndForgeRequiresACommitMessage(): void
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', false);

        $svn_look = $this->createMock(Svnlook::class);
        $svn_look->method('getMessageFromTransaction')->willReturn([""]);

        $hook_config = $this->createMock(\Tuleap\SVN\Repository\HookConfigRetriever::class);
        $hook_config->method('getHookConfig')->with(HookConfig::MANDATORY_REFERENCE)->willReturn(false);

        $hook = new PreCommit(
            $svn_look,
            $this->createMock(LoggerInterface::class),
            new CommitMessageValidator(
                $hook_config,
                $this->createMock(ReferenceManager::class),
            ),
        );

        $this->expectException(EmptyCommitMessageException::class);
        $hook->assertCommitIsValid($this->repository, '1');
    }

    public function testIDoesNotRejectCommitIfCommitMessageIsEmptyAndForgeDoesNotRequireACommitMessage(): void
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', true);

        $svn_look = $this->createMock(Svnlook::class);
        $svn_look->method('getMessageFromTransaction')->willReturn([""]);
        $svn_look->method('getTransactionPath')->willReturn([]);

        $hook_config_retriever = $this->createMock(\Tuleap\SVN\Repository\HookConfigRetriever::class);

        $hook_config = $this->createMock(HookConfig::class);
        $hook_config_retriever->method('getHookConfig')
            ->with($this->repository)
            ->willReturn($hook_config);

        $hook_config->method('getHookConfig')->with(HookConfig::MANDATORY_REFERENCE)->willReturn(false);

        $hook = new PreCommit(
            $svn_look,
            new NullLogger(),
            new CommitMessageValidator(
                $hook_config_retriever,
                $this->createMock(ReferenceManager::class),
            ),
        );

        $this->expectNotToPerformAssertions();

        $hook->assertCommitIsValid($this->repository, '1');
    }

    public function testIRejectsCommitMessagesWithoutArtifactReference(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        $svn_look = $this->createMock(Svnlook::class);
        $svn_look->method('getMessageFromTransaction')->willReturn(["Commit message without reference"]);

        $hook_config_retriever = $this->createMock(\Tuleap\SVN\Repository\HookConfigRetriever::class);

        $hook_config = $this->createMock(HookConfig::class);
        $hook_config_retriever->method('getHookConfig')
            ->with($this->repository)
            ->willReturn($hook_config);

        $hook_config->method('getHookConfig')->with(HookConfig::MANDATORY_REFERENCE)->willReturn(true);

        $reference_manager = $this->createMock(ReferenceManager::class);

        $this->repository->expects(self::once())->method('getProject')->willReturn($project);
        $reference_manager->expects(self::once())
            ->method('stringContainsReferences')
            ->with("Commit message without reference", self::anything())
            ->willReturn(false);

        $hook = new PreCommit(
            $svn_look,
            new NullLogger(),
            new CommitMessageValidator(
                $hook_config_retriever,
                $reference_manager,
            ),
        );

        $this->expectException(CommitMessageWithoutReferenceException::class);
        $hook->assertCommitIsValid($this->repository, '1');
    }
}
