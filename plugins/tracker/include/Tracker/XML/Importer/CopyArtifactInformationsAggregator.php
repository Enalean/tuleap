<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Tracker_XML_Importer_CopyArtifactInformationsAggregator extends \Psr\Log\AbstractLogger implements LoggerInterface
{
    /** @var string[] */
    private $logs_stack = [];

    /** @var LoggerInterface */
    private $backend_logger;

    public function __construct(LoggerInterface $backend_logger)
    {
        $this->backend_logger = $backend_logger;
    }

    public function getAllLogs()
    {
        return $this->logs_stack;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (
            $level === LogLevel::WARNING || $level === LogLevel::ERROR || $level === LogLevel::CRITICAL ||
            $level === LogLevel::EMERGENCY || $level === LogLevel::ALERT
        ) {
            $this->logs_stack[] = "[$level] $message";
        }
        $this->backend_logger->log($level, $message, $context);
    }
}
