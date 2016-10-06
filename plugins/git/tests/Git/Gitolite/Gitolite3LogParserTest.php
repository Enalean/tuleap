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

    public function setUp()
    {
        parent::setUp();
        $this->logger = mock('GitBackendLogger');
        $this->parser = new Gitolite3LogParser(
            $this->logger,
            mock('System_Command'),
            mock('Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator')
        );
    }

    public function itOnlyParseNonGitAdminLogs()
    {
        $this->logger->expectCallCount('debug', 2);
        $this->parser->parseLogs(dirname(__FILE__) . '/_fixtures/');
    }
}
