<?php
/**
 * Copyright (c) Enalean, 2016-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use GitRepository;
use GitRepositoryFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Git\History\Dao;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserDao;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Gitolite3LogParserTest extends TestCase
{
    private Gitolite3LogParser $parser;
    private Dao&MockObject $history_dao;
    private GitoliteFileLogsDao&MockObject $file_logs_dao;
    private UserManager&MockObject $user_manager;
    private GitRepositoryFactory&MockObject $factory;
    private UserDao&MockObject $user_dao;
    private GitRepository $repository;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->factory       = $this->createMock(GitRepositoryFactory::class);
        $this->user_manager  = $this->createMock(UserManager::class);
        $this->history_dao   = $this->createMock(Dao::class);
        $this->file_logs_dao = $this->createMock(GitoliteFileLogsDao::class);
        $this->user_dao      = $this->createMock(UserDao::class);
        $this->parser        = new Gitolite3LogParser(
            new NullLogger(),
            new HttpUserValidator(),
            $this->history_dao,
            $this->factory,
            $this->user_manager,
            $this->file_logs_dao,
            $this->user_dao
        );

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build();
        $this->user       = UserTestBuilder::buildWithId(101);
        $this->file_logs_dao->method('storeLastLine');
        $this->history_dao->method('startTransaction');
        $this->history_dao->method('commit');
        $this->user_dao->method('storeLastAccessDate');
    }

    public function testItDoesNotParseGitoliteAdministratorLogs(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);
        $this->file_logs_dao->method('getLastReadLine');

        $this->history_dao->expects($this->once())->method('addGitReadAccess')->with(20161004, 1, 101, 2, 1475566423);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItDoesNotParseGerritSystemUsers(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);
        $this->file_logs_dao->method('getLastReadLine');

        $this->history_dao->expects($this->once())->method('addGitReadAccess')->with(20161004, 1, 101, 2, 1475566423);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-11.log');
    }

    public function testItDoesNotParseTwoTimesSameLines(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);
        $this->file_logs_dao->method('getLastReadLine')->willReturn(['end_line' => 2259]);

        $this->history_dao->expects($this->never())->method('addGitReadAccess');
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItDoesNotParseWhenRepositoryIsDeleted(): void
    {
        $this->factory->method('getFromFullPath')->willReturn(null);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);
        $this->file_logs_dao->method('getLastReadLine');

        $this->history_dao->expects($this->never())->method('addGitReadAccess');
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItParseLinesIfTheyAreNew(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->file_logs_dao->method('getLastReadLine')->willReturn(['end_line' => 1362]);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);

        $this->history_dao->expects($this->never())->method('addGitReadAccess');
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItAddsALineForAnonymousWhenUserIsNoMoreInDatabase(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn(null);
        $this->file_logs_dao->method('getLastReadLine');

        $this->history_dao->expects($this->once())->method('addGitReadAccess')->with(20161004, 1, 0, 2, 1475566423);

        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItUpdatesTheCounterWhenThereAreAlreadyData(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);
        $this->file_logs_dao->method('getLastReadLine');

        $this->history_dao->expects($this->once())->method('addGitReadAccess')->with(20161004, 1, 101, 2, 1475566423);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItParsesWronglyFormattedLogsWithoutErrors(): void
    {
        $this->user_manager->method('getUserByUserName');
        $this->factory->expects($this->atLeastOnce())->method('getFromFullPath')->willReturn($this->repository);
        $this->file_logs_dao->method('getLastReadLine');
        $this->parser->parseLogs(__DIR__ . '/_fixtures/gitolite-2017-11-broken.log');
    }

    public function testItUpdatesLastAccessDateForUser(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn($this->user);
        $this->file_logs_dao->method('getLastReadLine');
        $this->history_dao->method('addGitReadAccess');

        $this->user_dao->expects($this->once())->method('storeLastAccessDate')->with(101, self::anything());

        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItDoesNotUpdateLastAccessDateForAnonymousUser(): void
    {
        $this->factory->method('getFromFullPath')->willReturn($this->repository);
        $this->user_manager->method('getUserByUserName')->willReturn(null);
        $this->file_logs_dao->method('getLastReadLine');
        $this->history_dao->method('addGitReadAccess');

        $this->user_dao->expects($this->never())->method('storeLastAccessDate');

        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }
}
