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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\Config\ConfigKey;
use Tuleap\Log\LogToFiles;
use Tuleap\Log\LogToGraylog2;
use Tuleap\Log\LogToSyslog;

class BackendLogger extends \Psr\Log\AbstractLogger implements LoggerInterface
{
    #[ConfigKey("Which logger should be used ('files', 'syslog', 'graylog2'). Default is 'files'")]
    public const CONFIG_LOGGER = 'sys_logger';

    public const FILENAME = 'codendi_syslog';

    private $filepath;

    public function __construct($filename = null)
    {
        $this->filepath = empty($filename) ? ForgeConfig::get('codendi_log') . '/' . self::FILENAME : $filename;
    }

    public static function isLogHandlerToFiles(): bool
    {
        return ForgeConfig::get(self::CONFIG_LOGGER, LogToFiles::CONFIG_LOGGER_FILES) === LogToFiles::CONFIG_LOGGER_FILES;
    }

    public static function getDefaultLogger(string $name = 'default'): LoggerInterface
    {
        $message_to_log = null;
        try {
            $handler = ForgeConfig::get(self::CONFIG_LOGGER, LogToFiles::CONFIG_LOGGER_FILES);

            $logger = new \Monolog\Logger(self::convertLoggerFileNameToTopic($name));
            if ($handler === LogToGraylog2::CONFIG_LOGGER_GRAYLOG2) {
                return (new LogToGraylog2())->configure(
                    $logger,
                    \Monolog\Logger::toMonologLevel(self::getPSR3LoggerLevel()),
                );
            }
            if ($handler === LogToSyslog::CONFIG_LOGGER_SYSLOG) {
                return (new LogToSyslog())->configure(
                    $logger,
                    \Monolog\Logger::toMonologLevel(self::getPSR3LoggerLevel()),
                );
            }
        } catch (\Tuleap\Log\UnableToSetupHandlerException $exception) {
            $message_to_log = $exception->getMessage();
        }

        $logger = (new LogToFiles())->getLogger($name);
        if ($message_to_log !== null) {
            $logger->warning($message_to_log);
        }

        return $logger;
    }

    private static function convertLoggerFileNameToTopic(string $name): string
    {
        return str_replace(['.log', '_syslog'], '', $name);
    }

    /**
     * @psalm-return "debug"|"info"|"warning"|"error"
     */
    private static function getPSR3LoggerLevel(): string
    {
        $level = ForgeConfig::get('sys_logger_level');
        if (! $level || $level === 'warn') {
            return LogLevel::WARNING;
        }
        return $level;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $pid = getmypid();

        $message = $this->generateLogWithException($message, $context);

        if (! \is_file($this->filepath)) {
            $sys_http_user = ForgeConfig::get('sys_http_user');
            if (! touch($this->filepath) || ! chown($this->filepath, $sys_http_user) || ! chgrp($this->filepath, $sys_http_user)) {
                throw new \RuntimeException(sprintf("Could not create or set the appropriate owner to the log file %s", $this->filepath));
            }
        }

        error_log(date('c') . " [$pid] [$level] $message\n", 3, $this->filepath);
    }

    private function generateLogWithException($message, array $context): string
    {
        $log_string = $message;
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exception     = $context['exception'];
            $error_message = $exception->getMessage();
            $stack_trace   = $exception->getTraceAsString();
            $log_string   .= ": $error_message:\n$stack_trace";
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
