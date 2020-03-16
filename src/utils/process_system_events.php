<?php
/**
 * Copyright (c) Enalean SAS, 2013-present. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once __DIR__ . '/../vendor/autoload.php';

$request_queue = (isset($argv[1])) ? $argv[1] : SystemEvent::DEFAULT_QUEUE;

// Rewrite as a process wrapper to ensure that process names are the same, so running
// $> tuleap process-system-event
// and
// $> /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/process_system_events.php
// will be shown as the same process. Otherwise names would be different and then would be run concurrently
// Note this is kept for backward compatibility purpose (install, docker images) in 2019, at some point it
// could probably be removed.
$process = new \Symfony\Component\Process\Process(['/usr/bin/tuleap', \Tuleap\CLI\Command\ProcessSystemEventsCommand::NAME, $request_queue]);
$process->run();
if (! $process->isSuccessful()) {
    fwrite(STDERR, sprintf("ERROR, command %s exited with %d. Stderr:\n%s\nStdout:\n%s\n", $process->getCommandLine(), (int) $process->getExitCode(), $process->getErrorOutput(), $process->getOutput()));
}

exit($process->getExitCode());
