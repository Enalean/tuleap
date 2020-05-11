<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Log;

trait LogForwarderTrait
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function emergency($message, array $context = [])
    {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }
}
