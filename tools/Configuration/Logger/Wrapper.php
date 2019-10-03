<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Configuration\Logger;

class Wrapper implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $prefix = array();

    public function __construct(LoggerInterface $logger, $prefix)
    {
        $this->logger   = $logger;
        $this->prefix[] = $prefix;
    }

    public function debug($message, array $context = array())
    {
        $this->logger->debug($this->formatMessage($message), $context);
    }

    public function info($message, array $context = array())
    {
        $this->logger->info($this->formatMessage($message), $context);
    }

    public function warn($message, array $context = array())
    {
        $this->logger->warn($this->formatMessage($message), $context);
    }

    public function error($message, array $context = array())
    {
        $this->logger->error($this->formatMessage($message), $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $this->formatMessage($message), $context);
    }

    private function formatMessage($message)
    {
        return '['. implode('][', $this->prefix) .'] '.$message;
    }
}
