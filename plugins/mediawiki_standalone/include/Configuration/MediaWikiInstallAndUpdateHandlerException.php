<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

final class MediaWikiInstallAndUpdateHandlerException extends \RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct("Could not execute MW install and update scripts:\n$message");
    }

    /**
     * @param non-empty-array<MediaWikiManagementCommandFailure> $failures
     */
    public static function fromCommandFailures(array $failures): self
    {
        $error_messages = [];
        foreach ($failures as $failure) {
            $exit_code            = $failure->exit_code;
            $process_command_line = $failure->process_command_line;
            $process_output       = $failure->process_output;
            $error_messages[]     = "Exit code: $exit_code\nProcess command line: $process_command_line\nProcess output: $process_output";
        }
        return new self(implode("\n------\n", $error_messages));
    }
}
