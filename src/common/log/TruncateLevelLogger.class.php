<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

class TruncateLevelLogger implements Logger
{

    /**
     * @var Logger
     */
    private $logger;

    private $level_weight = array(
        Logger::DEBUG => 0,
        Logger::INFO  => 10,
        Logger::WARN  => 20,
        Logger::ERROR => 30,
    );

    public function __construct(Logger $logger, $level)
    {
        $this->logger = $logger;
        $this->setLevel($level);
    }

    private function setLevel($min_level)
    {
        $min_weight = $this->level_weight[$min_level];
        foreach ($this->level_weight as $level_label => $weight) {
            $this->should_log[$level_label] = $weight >= $min_weight;
        }
    }

    public function debug($message)
    {
        if ($this->should_log[Logger::DEBUG]) {
            $this->logger->debug($message);
        }
    }

    public function info($message)
    {
        if ($this->should_log[Logger::INFO]) {
            $this->logger->info($message);
        }
    }

    public function warn($message, ?Exception $exception = null)
    {
        if ($this->should_log[Logger::WARN]) {
            $this->logger->warn($message, $exception);
        }
    }

    public function error($message, ?Exception $exception = null)
    {
        $this->logger->error($message, $exception);
    }

    public function log($message, $level = null)
    {
        switch ($level) {
            case Logger::DEBUG:
                $this->debug($message);
                break;
            case Logger::INFO:
                $this->info($message);
                break;
            case Logger::WARN:
                $this->warn($message);
                break;
            case Logger::ERROR:
                $this->error($message);
                break;
        }
    }

    public function createLogFileForAppUser($file_path)
    {
        if (! is_file($file_path)) {
            $http_user = ForgeConfig::get('sys_http_user');
            touch($file_path);
            chown($file_path, $http_user);
            chgrp($file_path, $http_user);
        }
    }
}
