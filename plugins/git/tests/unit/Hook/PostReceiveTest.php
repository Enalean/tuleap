<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use Mockery;
use Tuleap\Git\DefaultBranch\DefaultBranchPostReceiveUpdater;
use Tuleap\Git\Hook\DefaultBranchPush\PushAnalyzer;
use Tuleap\Git\Tests\Stub\VerifyIsDefaultBranchStub;
use Tuleap\Git\Webhook\WebhookRequestSender;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EnqueueTaskStub;

final class PostReceiveTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    private const MASTER_REF_NAME = 'refs/heads/master';
    private const OLD_REV_SHA1    = 'd8f1e57';
    private const NEW_REV_SHA1    = '469eaa9';
    private const REPOSITORY_PATH = '/var/lib/tuleap/gitolite/repositories/garden/dev.git';

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface & LogAnalyzer
     */
    private $log_analyzer;
    /**
     * @var \GitRepositoryFactory & Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_repository_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface & \UserManager
     */
    private $user_manager;
    /**
     * @var \GitRepository & \PHPUnit\Framework\MockObject\Stub
     */
    private $repository;
    /**
     * @var \Git_Ci_Launcher & Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $ci_launcher;
    private \PFUser $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface & ParseLog
     */
    private $parse_log;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DefaultBranchPostReceiveUpdater
     */
    private $default_branch_post_receive_updater;
    private VerifyIsDefaultBranchStub $default_branch_verifier;
    private EnqueueTaskStub $enqueuer;

    protected function setUp(): void
    {
        $this->user                                = UserTestBuilder::buildWithDefaults();
        $this->log_analyzer                        = \Mockery::spy(LogAnalyzer::class);
        $this->git_repository_factory              = \Mockery::spy(\GitRepositoryFactory::class);
        $this->user_manager                        = \Mockery::spy(\UserManager::class);
        $this->repository                          = $this->createStub(\GitRepository::class);
        $this->ci_launcher                         = \Mockery::spy(\Git_Ci_Launcher::class);
        $this->parse_log                           = \Mockery::spy(ParseLog::class);
        $this->default_branch_post_receive_updater = $this->createMock(DefaultBranchPostReceiveUpdater::class);

        $this->repository->method('getNotifiedMails')->willReturn([]);
        $this->repository->method('getId')->willReturn(300);
        $this->repository->method('getFullName')->willReturn('foamflower/newmarket');
        $this->repository->method('getFullPath')->willReturn(
            '/var/lib/tuleap/gitolite/repositories/foamflower/newmarket.git'
        );
        $project = ProjectTestBuilder::aProject()->build();
        $this->repository->method('getProject')->willReturn($project);

        $this->enqueuer = new EnqueueTaskStub();
    }

    private function executePostReceive(): void
    {
        $post_receive = new PostReceive(
            $this->log_analyzer,
            $this->git_repository_factory,
            $this->user_manager,
            $this->ci_launcher,
            $this->parse_log,
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(WebhookRequestSender::class),
            \Mockery::spy(PostReceiveMailSender::class),
            $this->createStub(DefaultBranchPostReceiveUpdater::class),
            new PushAnalyzer(VerifyIsDefaultBranchStub::withAlwaysDefaultBranch()),
            $this->enqueuer
        );
        $post_receive->execute(
            self::REPOSITORY_PATH,
            'john_doe',
            self::OLD_REV_SHA1,
            self::NEW_REV_SHA1,
            self::MASTER_REF_NAME,
        );
    }

    public function testItGetRepositoryFromFactory(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->with(
            self::REPOSITORY_PATH
        )->once();

        $this->executePostReceive();
    }

    public function testItGetUserFromManager(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->with('john_doe')->once();
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->getPushDetailsWithoutRevisions());

        $this->executePostReceive();
    }

    public function testItSkipsIfRepositoryIsNotKnown(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns(null);

        $this->parse_log->shouldReceive('execute')->never();
        $this->executePostReceive();
    }

    public function testItFallsBackOnAnonymousIfUserIsNotKnown(): void
    {
        $this->expectNotToPerformAssertions();
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->getPushDetailsWithoutRevisions());

        $this->executePostReceive();
    }

    public function testItGetsPushDetailsFromLogAnalyzer(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->log_analyzer->shouldReceive('getPushDetails')
            ->with($this->repository, Mockery::any(), self::OLD_REV_SHA1, self::NEW_REV_SHA1, self::MASTER_REF_NAME)
            ->once()
            ->andReturns($this->getPushDetailsWithoutRevisions());

        $this->executePostReceive();
    }

    public function testItExecutesExtractOnEachCommit(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);
        $push_details = $this->getPushDetailsWithNewRevision();
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($push_details);

        $this->parse_log->shouldReceive('execute')->with($push_details)->once();
        $this->executePostReceive();
    }

    public function testItTriggersACiBuild(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->getPushDetailsWithNewRevision());

        $this->ci_launcher->shouldReceive('executeForRepository')->with($this->repository)->once();
        $this->executePostReceive();
    }

    public function testItDispatchesAnAsynchronousMessage(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->getPushDetailsWithNewRevision());

        $this->executePostReceive();

        self::assertNotNull($this->enqueuer->queue_task);
    }

    private function getPushDetailsWithoutRevisions(): PushDetails
    {
        return new PushDetails(
            $this->repository,
            $this->user,
            self::MASTER_REF_NAME,
            PushDetails::ACTION_UPDATE,
            PushDetails::OBJECT_TYPE_COMMIT,
            []
        );
    }

    private function getPushDetailsWithNewRevision(): PushDetails
    {
        return new PushDetails(
            $this->repository,
            $this->user,
            self::MASTER_REF_NAME,
            PushDetails::ACTION_UPDATE,
            PushDetails::OBJECT_TYPE_COMMIT,
            [self::NEW_REV_SHA1]
        );
    }

    private function beforeParsing(): void
    {
        $post_receive = new PostReceive(
            $this->createStub(LogAnalyzer::class),
            $this->git_repository_factory,
            $this->createStub(\UserManager::class),
            $this->createStub(\Git_Ci_Launcher::class),
            $this->createStub(ParseLog::class),
            $this->createStub(\EventManager::class),
            $this->createStub(WebhookRequestSender::class),
            $this->createStub(PostReceiveMailSender::class),
            $this->default_branch_post_receive_updater,
            new PushAnalyzer(VerifyIsDefaultBranchStub::withAlwaysDefaultBranch()),
            $this->enqueuer
        );
        $post_receive->beforeParsingReferences(self::REPOSITORY_PATH);
    }

    public function testUpdatesDefaultBranch(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);

        $this->default_branch_post_receive_updater->expects(self::once())->method('updateDefaultBranchWhenNeeded');

        $this->beforeParsing();
    }
}
