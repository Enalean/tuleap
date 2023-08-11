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

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class IncludeBacktraceProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record['extra']['backtrace'] = '';
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $line) {
            if ($this->isFromLoggerStack($line)) {
                continue;
            }
            $record['extra']['backtrace'] .= $this->formatOneLine($line);
        }
        return $record;
    }

    /**
     * Exclude backtrace elements from the log stack itself (backtrace should stop where the ->log was called)
     */
    private function isFromLoggerStack(array $line): bool
    {
        if (isset($line['class']) && in_array($line['class'], [self::class, \Monolog\Logger::class], true)) {
            return true;
        }
        if (isset($line['function']) && isset($line['file']) && strpos($line['file'], 'Monolog/Logger.php') !== false) {
            return true;
        }
        return false;
    }

    private function formatOneLine(array $line): string
    {
        $string = '';
        if (isset($line['class']) && isset($line['function'])) {
            $string .= $line['class'] . $line['type'] . $line['function'];
        } elseif (isset($line['function'])) {
            $string .=  $line['function'];
        }
        if (isset($line['file'])) {
            $string .= ' ' . substr($line['file'], strlen('/usr/share/tuleap/')) . ':' . $line['line'] . PHP_EOL;
        }
        return $string;
    }
}
