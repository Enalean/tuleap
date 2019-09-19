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

class WrapperLogger implements Logger
{

    /**
     * @var Logger
     */
    private $logger;

    private $prefix = array();

    public function __construct(Logger $logger, $prefix)
    {
        $this->logger   = $logger;
        $this->prefix[] = $prefix;
    }

    public function debug($message)
    {
        $this->logger->debug($this->formatMessage($message));
    }

    public function error($message, ?Exception $exception = null)
    {
        $this->logger->error($this->formatMessage($message), $exception);
    }

    public function info($message)
    {
        $this->logger->info($this->formatMessage($message));
    }

    public function log($message, $level = null)
    {
        $this->logger->log($this->formatMessage($message), $level);
    }

    public function warn($message, ?Exception $exception = null)
    {
        $this->logger->warn($this->formatMessage($message), $exception);
    }

    private function formatMessage($message)
    {
        return '['. implode('][', $this->prefix) .'] '.$message;
    }

    public function push($prefix)
    {
        $this->prefix[] = $prefix;
    }

    public function pop()
    {
        array_pop($this->prefix);
    }
}
