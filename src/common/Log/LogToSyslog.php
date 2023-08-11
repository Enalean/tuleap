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

use Monolog\Handler\SyslogHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LogToSyslog
{
    public const CONFIG_LOGGER_SYSLOG = 'syslog';

    public function configure(Logger $logger, int|Level $level): LoggerInterface
    {
        $stream_handler = new SyslogHandler(
            'tuleap',
            LOG_USER,
            $level,
        );
        // Format borrowed from AbstractSyslogHandler::getDefaultFormatter
        // I considered creating a TuleapSyslogHandler to avoid duplication but I thought that the inheritance of
        // concrete class would hurt us more than a small duplication.
        // History will judge :D
        $line_formatter = new \Monolog\Formatter\LineFormatter('%channel%.%level_name%: %message% %context% %extra%');
        $line_formatter->includeStacktraces();
        $stream_handler->setFormatter($line_formatter);
        $logger->pushHandler($stream_handler);

        return $logger;
    }
}
