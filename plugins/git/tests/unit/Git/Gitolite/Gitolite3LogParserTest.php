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

namespace Tuleap\Git\Gitolite;

use Psr\Log\LoggerInterface;
use UserDao;
use Tuleap\Git\History\Dao;

class Gitolite3LogParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Gitolite3LogParser */
    private $parser;

    /** @var  LoggerInterface */
    private $logger;

    /** @var  Dao */
    private $history_dao;

    /** @var Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator */
    private $user_validator;

    /** @var Tuleap\Git\Gitolite\GitoliteFileLogsDao */
    private $file_logs_dao;
    private $user_manager;
    private $factory;
    /**
     * @var \UserDao&\Mockery\MockInterface
     */
    private $user_dao;
    /**
     * @var \GitRepository&\Mockery\MockInterface
     */
    private $repository;
    /**
     * @var \Mockery\MockInterface&\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger         = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->factory        = \Mockery::spy(\GitRepositoryFactory::class);
        $this->user_manager   = \Mockery::spy(\UserManager::class);
        $this->history_dao    = \Mockery::spy(Dao::class);
        $this->user_validator = new \Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator();
        $this->file_logs_dao  = \Mockery::spy(GitoliteFileLogsDao::class);
        $this->user_dao       = \Mockery::spy(UserDao::class);
        $this->parser         = new Gitolite3LogParser(
            $this->logger,
            $this->user_validator,
            $this->history_dao,
            $this->factory,
            $this->user_manager,
            $this->file_logs_dao,
            $this->user_dao
        );

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns(1);

        $this->user = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns(101)->getMock();
    }

    public function testItDoesNotParseGitoliteAdministratorLogs(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->history_dao->shouldReceive('addGitReadAccess')->with(20161004, 1, 101, 2, 1475566423)->once();
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItDoesNotParseGerritSystemUsers(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->history_dao->shouldReceive('addGitReadAccess')->with(20161004, 1, 101, 2, 1475566423)->once();
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-11.log');
    }

    public function testItDoesNotParseTwoTimesSameLines(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);
        $this->file_logs_dao->shouldReceive('getLastReadLine')->andReturns(['end_line' => 2259]);

        $this->history_dao->shouldReceive('addGitReadAccess')->never();
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItDoesNotParseWhenRepositoryIsDeleted(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns(null);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->history_dao->shouldReceive('addGitReadAccess')->never();
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItParseLinesIfTheyAreNew(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->file_logs_dao->shouldReceive('getLastReadLine')->andReturns(['end_line' => 1362]);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->history_dao->shouldReceive('addGitReadAccess')->never();
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItAddsALineForAnonymousWhenUserIsNoMoreInDatabase(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);

        $this->history_dao->shouldReceive('addGitReadAccess')->with(20161004, 1, 0, 2, 1475566423)->once();

        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItUpdatesTheCounterWhenThereAreAlreadyData(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->history_dao->shouldReceive('addGitReadAccess')->with(20161004, 1, 101, 2, 1475566423)->once();
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItParsesWronglyFormattedLogsWithoutErrors(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository)->atLeast()->once();
        $this->parser->parseLogs(__DIR__ . '/_fixtures/gitolite-2017-11-broken.log');
    }

    public function testItUpdatesLastAccessDateForUser(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);

        $this->user_dao->shouldReceive('storeLastAccessDate')->with(101, \Mockery::any())->once();

        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function testItDoesNotUpdateLastAccessDateForAnonymousUser(): void
    {
        $this->factory->shouldReceive('getFromFullPath')->andReturns($this->repository);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);

        $this->user_dao->shouldReceive('storeLastAccessDate')->never();

        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }
}
