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

use Tuleap\ForgeConfigSandbox;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PreCommitMessageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /** @var SVN_Svnlook */
    protected $svn_look;

    /** @var Project */
    protected $project;

    /** @var SVN_Immutable_Tags_Handler */
    protected $handler;
    private string $repo;
    private string $commit_message;
    private string $transaction;
    /**
     * @var SVN_CommitMessageValidator&\Mockery\MockInterface
     */
    private $commit_message_validator;
    private SVN_Hook_PreCommit $pre_commit;
    /**
     * @var \Mockery\MockInterface&SVN_Hooks
     */
    private $svn_hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo           = 'SVN_repo';
        $this->commit_message = '';
        $this->transaction    = '1';
        $this->project        = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();

        $this->svn_hook = $this->createMock(\SVN_Hooks::class);
        $this->svn_hook->method('getProjectFromRepositoryPath')->with($this->repo)->willReturn($this->project);

        $this->commit_message_validator = $this->createMock(\SVN_CommitMessageValidator::class);

        $this->svn_look = $this->createMock(\SVN_Svnlook::class);
        $this->handler  = $this->createMock(\SVN_Immutable_Tags_Handler::class);

        $this->pre_commit = new SVN_Hook_PreCommit(
            $this->svn_hook,
            $this->commit_message_validator,
            $this->svn_look,
            $this->handler,
            $this->createMock(\Tuleap\SVNCore\SHA1CollisionDetector::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
    }

    public function testItRejectsCommitIfCommitMessageIsEmptyAndForgeRequiresACommitMessage()
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', false);

        $this->expectException(\Exception::class);
        $this->commit_message_validator->expects(self::never())->method('assertCommitMessageIsValid');

        $this->pre_commit->assertCommitMessageIsValid($this->repo, $this->commit_message);
    }

    public function testItDoesNotRejectCommitIfCommitMessageIsEmptyAndForgeDoesNotRequireACommitMessage()
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', true);

        $this->commit_message_validator->expects(self::once())->method('assertCommitMessageIsValid');

        $this->pre_commit->assertCommitMessageIsValid($this->repo, $this->commit_message);
    }
}
