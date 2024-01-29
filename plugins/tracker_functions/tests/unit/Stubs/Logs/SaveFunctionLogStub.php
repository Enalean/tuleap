<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TrackerFunctions\Stubs\Logs;

use Tuleap\TrackerFunctions\Logs\FunctionLogLine;
use Tuleap\TrackerFunctions\Logs\SaveFunctionLog;

final class SaveFunctionLogStub implements SaveFunctionLog
{
    private ?FunctionLogLine $line_saved = null;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function saveFunctionLogLine(FunctionLogLine $log_line): void
    {
        $this->line_saved = $log_line;
    }

    public function getLineSaved(): ?FunctionLogLine
    {
        return $this->line_saved;
    }
}
