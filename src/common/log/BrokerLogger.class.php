<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class BrokerLogger extends \Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface[]
     */
    private $loggers = [];

    /**
     * @param \Psr\Log\LoggerInterface[] $loggers
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
