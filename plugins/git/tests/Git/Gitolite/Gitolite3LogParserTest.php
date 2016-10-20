<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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

require_once dirname(__FILE__) . '/../../bootstrap.php';

use GitBackendLogger;

class Gitolite3LogParserTest extends \TuleapTestCase
{

    /** @var Gitolite3LogParser */
    private $parser;

    /** @var  GitBackendLogger */
    private $logger;

    /** @var  Dao */
    private $history_dao;

    /** @var Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator */
    private $user_validator;

    /** @var Tuleap\Git\Gitolite\GitoliteFileLogsDao */
    private $file_logs_dao;

    public function setUp()
    {
        parent::setUp();
        $this->logger         = mock('GitBackendLogger');
        $this->factory        = mock('GitRepositoryFactory');
        $user_manager         = mock('UserManager');
        $this->history_dao    = mock('Tuleap\Git\History\Dao');
        $this->user_validator = mock('Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator');
        $this->file_logs_dao  = mock('Tuleap\Git\Gitolite\GitoliteFileLogsDao');
        $this->parser         = new Gitolite3LogParser(
            $this->logger,
            mock('System_Command'),
            $this->user_validator,
            $this->history_dao,
            $this->factory,
            $user_manager,
            $this->file_logs_dao
        );

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(1);

        $user = mock('PFUser');
        stub($user_manager)->getUserByUserName()->returns($user);
        stub($user)->getId()->returns(101);
    }

    public function itDoesNotParseGitoliteAdministratorLogs()
    {
        stub($this->factory)->getFromFullPath()->returns($this->repository);
        $this->history_dao->expectCallCount('insertGitReadAccess', 2);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function itDoesNotParseGerritSystemUsers()
    {
        stub($this->factory)->getFromFullPath()->returns($this->repository);
        $this->history_dao->expectCallCount('insertGitReadAccess', 3);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-11.log');
    }

    public function itDoesNotParseTwoTimesSameLines()
    {
        stub($this->factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_validator)->isLoginAnHTTPUserLogin()->returns(false);
        stub($this->file_logs_dao)->getLastReadLine()->returns(array('end_line' => 2259));
        $this->history_dao->expectCallCount('insertGitReadAccess', 0);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function itDoesNotParseWhenRepositoryIsDeleted()
    {
        stub($this->factory)->getFromFullPath()->returns(null);
        $this->history_dao->expectCallCount('insertGitReadAccess', 0);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }

    public function itParseLinesIfTheyAreNew()
    {
        stub($this->factory)->getFromFullPath()->returns($this->repository);
        stub($this->user_validator)->isLoginAnHTTPUserLogin()->returns(false);
        stub($this->file_logs_dao)->getLastReadLine()->returns(array('end_line' => 1362));
        $this->history_dao->expectCallCount('insertGitReadAccess', 0);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/gitolite-2016-10.log');
    }
}
