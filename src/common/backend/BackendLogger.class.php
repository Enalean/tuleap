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

class BackendLogger extends \Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface
{
    public const FILENAME = 'codendi_syslog';

    private $filepath;

    public function __construct($filename = null)
    {
        $this->filepath = empty($filename) ? ForgeConfig::get('codendi_log') . '/' . self::FILENAME : $filename;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public static function getDefaultLogger()
    {
        return new TruncateLevelLogger(
            new BackendLogger(),
            ForgeConfig::get('sys_logger_level')
        );
    }

    public function getFilepath()
    {
        return $this->filepath;
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
}
