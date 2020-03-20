#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';

use Tuleap\Git\Gitolite\Gitolite3LogParser;
use Tuleap\Git\Gitolite\GitoliteFileLogsDao;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\History\Dao;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;

$console    = new Log_ConsoleLogger();
$logger     = \BackendLogger::getDefaultLogger(GitPlugin::LOG_IDENTIFIER);
$broker_log = new BrokerLogger(array($logger, $console));

$detector = new VersionDetector();
if (! $detector->isGitolite3()) {
    $broker_log->error("You are currently using Gitolite2. Parsing logs only works for Gitolite3.");
    exit(1);
}

$broker_log->info("Starting parse gitolite3 logs.");
$gitolite_parser = new Gitolite3LogParser(
    $broker_log,
    new HttpUserValidator(),
    new Dao(),
    new GitRepositoryFactory(new GitDao(), ProjectManager::instance()),
    UserManager::instance(),
    new GitoliteFileLogsDao(),
    new UserDao()
);
$gitolite_parser->parseAllLogs(GITOLITE3_LOGS_PATH);

$broker_log->info("Logs parsed with success.");

exit(1);
