<?php
/**
 * Copyright Enalean (c) 2013-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

require_once __DIR__ . '/../../bootstrap.php';

class Git_Hook_PostReceive_CommonTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;
    protected $log_analyzer;
    protected $git_repository_factory;
    protected $post_receive;
    protected $user_manager;
    protected $repository;
    protected $ci_launcher;
    protected $user;
    protected $parse_log;
    protected $git_repository_url_manager;
    protected $system_event_manager;
    protected $mail_builder;
    protected $event_manager;

    protected function setUp() : void
    {
        parent::setUp();
        $this->user                       = \Mockery::spy(\PFUser::class);
        $this->log_analyzer               = \Mockery::spy(\Git_Hook_LogAnalyzer::class);
        $this->git_repository_factory     = \Mockery::spy(\GitRepositoryFactory::class);
        $this->user_manager               = \Mockery::spy(\UserManager::class);
        $this->repository                 = \Mockery::spy(\GitRepository::class);
        $this->ci_launcher                = \Mockery::spy(\Git_Ci_Launcher::class);
        $this->parse_log                  = \Mockery::spy(\Git_Hook_ParseLog::class);
        $this->system_event_manager       = \Mockery::spy(\Git_SystemEventManager::class);
        $this->mail_builder               = \Mockery::spy(\MailBuilder::class);
        $this->event_manager              = \Mockery::spy(\EventManager::class);
        $this->request_sender             = \Mockery::spy(\Tuleap\Git\Webhook\WebhookRequestSender::class);

        $this->post_receive = new Git_Hook_PostReceive(
            $this->log_analyzer,
            $this->git_repository_factory,
            $this->user_manager,
            $this->ci_launcher,
            $this->parse_log,
            $this->system_event_manager,
            $this->event_manager,
            $this->request_sender,
            \Mockery::spy(\Tuleap\Git\Hook\PostReceiveMailSender::class)
        );

        $this->repository->shouldReceive('getNotifiedMails')->andReturns(array());
    }

    public function testItGetRepositoryFromFactory() : void
    {
        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array())->getMock();

        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->git_repository_factory->shouldReceive('getFromFullPath')->with('/var/lib/tuleap/gitolite/repositories/garden/dev.git')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItGetUserFromManager() : void
    {
        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array())->getMock();

        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->with('john_doe')->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItSkipsIfRepositoryIsNotKnown() : void
    {
        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array())->getMock();

        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns(null);

        $this->parse_log->shouldReceive('execute')->never();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItFallsBackOnAnonymousIfUserIsNotKnows() : void
    {
        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array())->getMock();

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);

        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);

        $this->log_analyzer->shouldReceive('getPushDetails')
            ->with($this->repository, Mockery::any(), 'd8f1e57', '469eaa9', 'refs/heads/master')
            ->once()
            ->andReturns($this->push_details);

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItGetsPushDetailsFromLogAnalyzer() : void
    {
        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array())->getMock();

        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->log_analyzer->shouldReceive('getPushDetails')
            ->with($this->repository, $this->user, 'd8f1e57', '469eaa9', 'refs/heads/master')
            ->once()
            ->andReturns($this->push_details);

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItExecutesExtractOnEachCommit() : void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array('469eaa9'))->getMock();
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);

        $this->parse_log->shouldReceive('execute')->with($this->push_details)->once();

        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItTriggersACiBuild() : void
    {
        $this->push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(array('469eaa9'))->getMock();
        $this->log_analyzer->shouldReceive('getPushDetails')->andReturns($this->push_details);
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(\Mockery::spy(\PFUser::class));

        $this->ci_launcher->shouldReceive('executeForRepository')->with($this->repository)->once();
        $this->post_receive->execute('/var/lib/tuleap/gitolite/repositories/garden/dev.git', 'john_doe', 'd8f1e57', '469eaa9', 'refs/heads/master', $this->mail_builder);
    }

    public function testItLaunchesGrokMirrorUpdates() : void
    {
        $this->git_repository_factory->shouldReceive('getFromFullPath')->andReturns($this->repository);

        $this->system_event_manager->shouldReceive('queueGrokMirrorManifestFollowingAGitPush')->with($this->repository)->once();
        $this->post_receive->beforeParsingReferences('/var/lib/tuleap/gitolite/repositories/garden/dev.git');
    }
}
