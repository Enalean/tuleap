<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class BrokerLogger implements Logger
{

    private $loggers = array();

    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function debug($message)
    {
        foreach ($this->loggers as $logger) {
            $logger->debug($message);
        }
    }

    public function info($message)
    {
        foreach ($this->loggers as $logger) {
            $logger->info($message);
        }
    }

    public function warn($message, ?Exception $exception = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->warn($message, $exception);
        }
    }

    public function error($message, ?Exception $exception = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->error($message, $exception);
        }
    }

    public function log($message, $level = Logger::INFO)
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
}
