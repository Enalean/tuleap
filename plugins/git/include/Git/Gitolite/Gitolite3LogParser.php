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

use DirectoryIterator;
use Git;
use GitRepositoryGitoliteAdmin;
use Logger;
use System_Command;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;

class Gitolite3LogParser
{
    const GIT_COMMAND = 'pre_git';

    /** @var Logger */
    private $logger;

    /** @var System_Command */
    private $system_command;

    /**
     * @var HttpUserValidator
     */
    private $user_validator;

    public function __construct(Logger $logger, System_Command $system_command, HttpUserValidator $user_validator)
    {
        $this->logger         = $logger;
        $this->system_command = $system_command;
        $this->user_validator = $user_validator;
    }

    public function parseLogs($path)
    {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $file) {
            if (! $file->isDot() && preg_match('/^gitolite-\d{4}-\d{2}.log$/', $file->getFilename())) {
                $this->parseFile($path . $file);
            }
        }
    }

    private function parseFile($log)
    {
        $log_file = fopen("$log", "r");
        if (! $log_file) {
            throw new CannotAccessToGitoliteLogException();
        } else {
            while (! feof($log_file)) {
                $log_line = fgetcsv($log_file, 0, "\t");
                if ($log_line !== false) {
                    $this->parseLine($log_line, $log);
                }
            }
            fclose($log_file);
        }
    }

    private function parseLine(array $line, $filename)
    {
        if ($this->isAReadAccess($line) && $this->isNotASystemUser($line[4])) {
            $this->logger->debug(
                'File ' . $filename . '. Add one Read access for repository ' . $line[3] . ' pattern ' . $line[7] . ' for user ' . $line[4]
            );
        }
    }

    private function isAReadAccess(array $line)
    {
        return $line[2] === self::GIT_COMMAND && $line[5] === Git::READ_PERM;
    }

    private function isNotASystemUser($user)
    {
        return $user !== GitRepositoryGitoliteAdmin::USERNAME && ! $this->user_validator->isLoginAnHTTPUserLogin($user);
    }
}
