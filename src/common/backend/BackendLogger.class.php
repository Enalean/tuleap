<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Monolog\Handler\SyslogHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class BackendLogger extends \Psr\Log\AbstractLogger implements LoggerInterface
{
    public const CONFIG_LOGGER = 'sys_logger';

    private const CONFIG_LOGGER_SYSLOG = 'syslog';

    public const FILENAME = 'codendi_syslog';

    private $filepath;

    public function __construct($filename = null)
    {
        $this->filepath = empty($filename) ? ForgeConfig::get('codendi_log') . '/' . self::FILENAME : $filename;
    }

    public static function getDefaultLogger(string $name = 'default'): LoggerInterface
    {
        if (ForgeConfig::get(self::CONFIG_LOGGER) === self::CONFIG_LOGGER_SYSLOG) {
            $logger = new \Monolog\Logger(self::convertLoggerFileNameToTopic($name));
            $stream_handler = new SyslogHandler(
                'tuleap',
                LOG_USER,
                \Monolog\Logger::toMonologLevel(self::getPSR3LoggerLevel()),
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
        if ($name === 'default') {
            $logger = new BackendLogger();
        } else {
            $logger = new BackendLogger(ForgeConfig::get('codendi_log') . '/' . $name);
        }
        return new TruncateLevelLogger(
            $logger,
            ForgeConfig::get('sys_logger_level')
        );
    }

    private static function convertLoggerFileNameToTopic(string $name): string
    {
        return str_replace(['.log', '_syslog'], '', $name);
    }

    private static function getPSR3LoggerLevel()
    {
        $level = ForgeConfig::get('sys_logger_level');
        if (! $level || $level === 'warn') {
            return LogLevel::WARNING;
        }
        return $level;
    }

    public function log($level, $message, array $context = [])
    {
        $pid = getmypid();

        $message = $this->generateLogWithException($message, $context);

        error_log(date('c') . " [$pid] [$level] $message\n", 3, $this->filepath);
    }

    private function generateLogWithException($message, array $context): string
    {
        $log_string = $message;
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exception      = $context['exception'];
            $error_message  = $exception->getMessage();
            $stack_trace    = $exception->getTraceAsString();
            $log_string    .= ": $error_message:\n$stack_trace";
        }
        return $log_string;
    }

    public function restoreOwnership(BackendSystem $backend_system)
    {
        $backend_system->changeOwnerGroupMode(
            $this->filepath,
            ForgeConfig::get('sys_http_user'),
            ForgeConfig::get('sys_http_user'),
            0640
        );
    }
}
