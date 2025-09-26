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

declare(strict_types=1);

namespace Tuleap\Git\Hook;

use EventManager;
use Git_Ci_Launcher;
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\DefaultBranch\DefaultBranchPostReceiveUpdater;
use Tuleap\Git\Hook\DefaultBranchPush\PushAnalyzer;
use Tuleap\Git\Tests\Stub\VerifyIsDefaultBranchStub;
use Tuleap\Git\Webhook\WebhookRequestSender;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\User\RetrieveUserByUserName;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostReceiveTest extends TestCase
{
    use GlobalLanguageMock;

    private const string MASTER_REF_NAME = 'refs/heads/master';
    private const string OLD_REV_SHA1    = 'd8f1e57';
    private const string NEW_REV_SHA1    = '469eaa9';
    private const string REPOSITORY_PATH = '/var/lib/tuleap/gitolite/repositories/garden/dev.git';

    private LogAnalyzer&MockObject $log_analyzer;
    private GitRepositoryFactory&MockObject $git_repository_factory;
    private RetrieveUserByUserName $user_retriever;
    private GitRepository $repository;
    private Git_Ci_Launcher&MockObject $ci_launcher;
    private PFUser $user;
    private ParseLog&MockObject $parse_log;
    private DefaultBranchPostReceiveUpdater&MockObject $default_branch_post_receive_updater;
    private VerifyIsDefaultBranchStub $default_branch_verifier;
    private EnqueueTaskStub $enqueuer;

    #[\Override]
    protected function setUp(): void
    {
        $this->user                                = UserTestBuilder::aUser()->withUserName('john_doe')->build();
        $this->log_analyzer                        = $this->createMock(LogAnalyzer::class);
        $this->git_repository_factory              = $this->createMock(GitRepositoryFactory::class);
        $this->user_retriever                      = ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults());
        $this->repository                          = $this->createStub(GitRepository::class);
        $this->ci_launcher                         = $this->createMock(Git_Ci_Launcher::class);
        $this->parse_log                           = $this->createMock(ParseLog::class);
        $this->default_branch_post_receive_updater = $this->createMock(DefaultBranchPostReceiveUpdater::class);

        $this->repository->method('getNotifiedMails')->willReturn([]);
        $this->repository->method('getId')->willReturn(300);
        $this->repository->method('getFullName')->willReturn('foamflower/newmarket');
        $this->repository->method('getFullPath')->willReturn('/var/lib/tuleap/gitolite/repositories/foamflower/newmarket.git');
        $project = ProjectTestBuilder::aProject()->build();
        $this->repository->method('getProject')->willReturn($project);

        $this->enqueuer = new EnqueueTaskStub();
    }

    private function executePostReceive(): void
    {
        $event_manager          = $this->createMock(EventManager::class);
        $mail_sender            = $this->createMock(PostReceiveMailSender::class);
        $webhook_request_sender = $this->createMock(WebhookRequestSender::class);
        $post_receive           = new PostReceive(
            $this->log_analyzer,
            $this->git_repository_factory,
            $this->user_retriever,
            $this->ci_launcher,
            $this->parse_log,
            $event_manager,
            $webhook_request_sender,
            $mail_sender,
            $this->createStub(DefaultBranchPostReceiveUpdater::class),
            new PushAnalyzer(VerifyIsDefaultBranchStub::withAlwaysDefaultBranch()),
            $this->enqueuer
        );
        $event_manager->method('processEvent');
        $webhook_request_sender->method('sendRequests');
        $mail_sender->method('sendMail');
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
        $this->git_repository_factory->expects($this->once())->method('getFromFullPath')->with(self::REPOSITORY_PATH);

        $this->executePostReceive();
    }

    public function testItGetUserFromManager(): void
    {
        $this->expectNotToPerformAssertions();
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);
        $this->log_analyzer->method('getPushDetails')->willReturn($this->getPushDetailsWithoutRevisions());
        $this->ci_launcher->method('executeForRepository');
        $this->parse_log->method('execute');

        $this->executePostReceive();
    }

    public function testItSkipsIfRepositoryIsNotKnown(): void
    {
        $this->git_repository_factory->method('getFromFullPath')->willReturn(null);

        $this->parse_log->expects($this->never())->method('execute');
        $this->executePostReceive();
    }

    public function testItFallsBackOnAnonymousIfUserIsNotKnown(): void
    {
        $this->expectNotToPerformAssertions();
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);
        $this->log_analyzer->method('getPushDetails')->willReturn($this->getPushDetailsWithoutRevisions());
        $this->ci_launcher->method('executeForRepository');
        $this->parse_log->method('execute');

        $this->executePostReceive();
    }

    public function testItGetsPushDetailsFromLogAnalyzer(): void
    {
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_retriever = ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$this->user]);

        $this->log_analyzer->expects($this->once())->method('getPushDetails')
            ->with($this->repository, self::anything(), self::OLD_REV_SHA1, self::NEW_REV_SHA1, self::MASTER_REF_NAME)
            ->willReturn($this->getPushDetailsWithoutRevisions());
        $this->ci_launcher->method('executeForRepository');
        $this->parse_log->method('execute');

        $this->executePostReceive();
    }

    public function testItExecutesExtractOnEachCommit(): void
    {
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_retriever = ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$this->user]);
        $push_details         = $this->getPushDetailsWithNewRevision();
        $this->log_analyzer->method('getPushDetails')->willReturn($push_details);

        $this->ci_launcher->method('executeForRepository');
        $this->parse_log->expects($this->once())->method('execute')->with($push_details);
        $this->executePostReceive();
    }

    public function testItTriggersACiBuild(): void
    {
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_retriever = ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$this->user]);
        $this->log_analyzer->method('getPushDetails')->willReturn($this->getPushDetailsWithNewRevision());

        $this->ci_launcher->expects($this->once())->method('executeForRepository')->with($this->repository);
        $this->parse_log->method('execute');
        $this->executePostReceive();
    }

    public function testItDispatchesAnAsynchronousMessage(): void
    {
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_retriever = ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$this->user]);
        $this->log_analyzer->method('getPushDetails')->willReturn($this->getPushDetailsWithNewRevision());
        $this->ci_launcher->method('executeForRepository');
        $this->parse_log->method('execute');

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
            $this->createStub(UserManager::class),
            $this->createStub(Git_Ci_Launcher::class),
            $this->createStub(ParseLog::class),
            $this->createStub(EventManager::class),
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
        $this->git_repository_factory->method('getFromFullPath')->willReturn($this->repository);

        $this->default_branch_post_receive_updater->expects($this->once())->method('updateDefaultBranchWhenNeeded');

        $this->beforeParsing();
    }
}
