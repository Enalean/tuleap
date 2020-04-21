<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PreCommitMessageTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /** @var SVN_Svnlook */
    protected $svn_look;

    /** @var Project */
    protected $project;

    /** @var SVN_Immutable_Tags_Handler */
    protected $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo           = 'SVN_repo';
        $this->commit_message = '';
        $this->transaction    = '1';
        $this->project        = \Mockery::spy(\Project::class);

        $this->svn_hook                 = \Mockery::spy(\SVN_Hooks::class)->shouldReceive('getProjectFromRepositoryPath')->with($this->repo)->andReturns($this->project)->getMock();
        $this->commit_message_validator = \Mockery::spy(\SVN_CommitMessageValidator::class);

        $this->svn_look = \Mockery::spy(\SVN_Svnlook::class);
        $this->handler  = \Mockery::spy(\SVN_Immutable_Tags_Handler::class);

        $this->pre_commit = new SVN_Hook_PreCommit(
            $this->svn_hook,
            $this->commit_message_validator,
            $this->svn_look,
            $this->handler,
            \Mockery::spy(\Tuleap\Svn\SHA1CollisionDetector::class),
            \Mockery::spy(\Psr\Log\LoggerInterface::class)
        );
    }

    public function testItRejectsCommitIfCommitMessageIsEmptyAndForgeRequiresACommitMessage()
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', false);

        $this->expectException(\Exception::class);
        $this->commit_message_validator->shouldReceive('assertCommitMessageIsValid')->never();

        $this->pre_commit->assertCommitMessageIsValid($this->repo, $this->commit_message);
    }

    public function testItDoesNotRejectCommitIfCommitMessageIsEmptyAndForgeDoesNotRequireACommitMessage()
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', true);

        $this->commit_message_validator->shouldReceive('assertCommitMessageIsValid')->once();

        $this->pre_commit->assertCommitMessageIsValid($this->repo, $this->commit_message);
    }
}
