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
use Tuleap\Test\Builders\UserTestBuilder;

final class PostReceiveTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface & LogAnalyzer
     */
    private $log_analyzer;
    /**
     * @var \GitRepositoryFactory & Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_repository_factory;
    private PostReceive $post_receive;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface & \UserManager
     */
    private $user_manager;
    /**
     * @var \GitRepository & Mockery\LegacyMockInterface|Mockery\MockInterface
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
     * @var \Git_SystemEventManager & Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $system_event_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DefaultBranchPostReceiveUpdater
     */
    private $default_branch_post_receive_updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & \Tuleap\Git\Hook\PushDetails
     */
    private $push_details;

    protected function setUp(): void
    {
        $this->user                                = UserTestBuilder::buildWithDefaults();
        $this->log_analyzer                        = \Mockery::spy(LogAnalyzer::class);
        $this->git_repository_factory              = \Mockery::spy(\GitRepositoryFactory::class);
        $this->user_manager                        = \Mockery::spy(\UserManager::class);
        $this->repository                          = \Mockery::spy(\GitRepository::class);
        $this->ci_launcher                         = \Mockery::spy(\Git_Ci_Launcher::class);
        $this->parse_log                           = \Mockery::spy(ParseLog::class);
        $this->system_event_manager                = \Mockery::spy(\Git_SystemEventManager::class);
        $this->request_sender                      = \Mockery::spy(\Tuleap\Git\Webhook\WebhookRequestSender::class);
        $this->default_branch_post_receive_updater = $this->createMock(DefaultBranchPostReceiveUpdater::class);

        $this->post_receive = new PostReceive(
            $this->log_analyzer,
            $this->git_repository_factory,
            $this->user_manager,
            $this->ci_launcher,
            $this->parse_log,
            $this->system_event_manager,
            \Mockery::spy(\EventManager::class),
            $this->request_sender,
            \Mockery::spy(PostReceiveMailSender::class),
            $this->default_branch_post_receive_updater
        );

        $this->repository->shouldReceive('getNotifiedMails')->andReturns([]);
    }

    public function testItGetRepositoryFromFactory(): void
    {
        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            []
        )->getMock();

        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->git_repository_factory->shouldReceive('getFromFullPath')->with(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git'
        )->once();
        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master'
        );
    }

    public function testItGetUserFromManager(): void
    {
        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            []
        )->getMock();

        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->with('john_doe')->once();
        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master',
        );
    }

    public function testItSkipsIfRepositoryIsNotKnown(): void
    {
        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            []
        )->getMock();

        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns(null);

        $this->parse_log->shouldReceive('execute')->never();

        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master',
        );
    }

    public function testItFallsBackOnAnonymousIfUserIsNotKnows(): void
    {
        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            []
        )->getMock();

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);

        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);

        $this->log_analyzer->shouldReceive('getPushDetails')
            ->with($this->repository, Mockery::any(), 'd8f1e57', '469eaa9', 'refs/heads/master')
            ->once()
            ->andReturns($this->push_details);

        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master',
        );
    }

    public function testItGetsPushDetailsFromLogAnalyzer(): void
    {
        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            []
        )->getMock();

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->log_analyzer->shouldReceive('getPushDetails')
            ->with($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master')
            ->once()
            ->andReturns($this->push_details);

        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master',
        );
    }

    public function testItExecutesExtractOnEachCommit(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            ['469eaa9']
        )->getMock();
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->parse_log->shouldReceive('execute')->with($this->push_details)->once();

        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master',
        );
    }

    public function testItTriggersACiBuild(): void
    {
        $this->push_details = \Mockery::spy(\Tuleap\Git\Hook\PushDetails::class)->shouldReceive('getRevisionList')->andReturns(
            ['469eaa9']
        )->getMock();
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(\Mockery::spy(\PFUser::class));

        $this->ci_launcher->shouldReceive('executeForRepository')->with($this->repository)->once();
        $this->post_receive->execute(
            '/var/lib/tuleap/gitolite/repositories/garden/dev.git',
            'john_doe',
            'd8f1e57',
            '469eaa9',
            'refs/heads/master',
        );
    }

    public function testItLaunchesGrokMirrorUpdates(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->default_branch_post_receive_updater->method('updateDefaultBranchWhenNeeded');

        $this->system_event_manager->shouldReceive('queueGrokMirrorManifestFollowingAGitPush')->with(
            $this->repository
        )->once();
        $this->post_receive->beforeParsingReferences('/var/lib/tuleap/gitolite/repositories/garden/dev.git');
    }

    public function testUpdatesDefaultBranch(): void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->system_event_manager->shouldReceive('queueGrokMirrorManifestFollowingAGitPush');

        $this->default_branch_post_receive_updater->expects(self::once())->method('updateDefaultBranchWhenNeeded');

        $this->post_receive->beforeParsingReferences('/var/lib/tuleap/gitolite/repositories/garden/dev.git');
    }
}
