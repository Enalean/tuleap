<?php
/* 
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

// Backend scripts should never ends because of lack of time or memory
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

// Only root is allowed to run this script
$processUser = posix_getpwuid(posix_geteuid());
$username = $processUser['name'];
if ($username != 'root') {
    echo "Must be root to run this script (currently:".$username.")\n";
    exit(1);
}

//
// Check that another instance of this script is not already running
//

// How it works:
// This script stores its process ID in /var/run/codendi_process_events.pid,
// and deletes it when it has finished.
// If the PID file already exists, it means either that an instance is currently
// running, or that a previous call to this script failed (and thus did not delete 
// the PID file). In this case, we send a signal to the process which PID is 
// in the file. If it returns TRUE, it means that there is a process
// with this PID running. Then, we check that the command running with this PID 
// contains the name of this script (process_system_events.php). In this case,
// we simply exit.

$pathToPidFile='/var/run/codendi_process_events.pid';

// See http://www.php.net/manual/en/function.posix-kill.php#49596
if (file_exists($pathToPidFile)) {
    $prevPid = file_get_contents($pathToPidFile);
    if(($prevPid !== FALSE) && posix_kill(rtrim($prevPid),0)) {
        // A program using this PID is currently running
        // It might be a PID number collision: check the program name
        $result = shell_exec('/bin/ps -A -o pid,command | grep "' . $prevPid . '" | grep process_system_events.php | grep -v "grep"');
        if ($result != '') {
            //echo "Error: Server is already running with PID: $prevPid\n";
            exit(-1);
        }
    }
}
// Store PID
if (file_put_contents($pathToPidFile,getmypid()) === FALSE) {
	echo "Process_system_events: Cannot write PID file\n";
}

// Now start the real script.
require_once 'pre.php';
require_once 'common/system_event/SystemEventProcessor.class.php';

$processor = new SystemEventProcessor(
    $system_event_manager,
    new SystemEventDao(),
    Backend::instance('Aliases'),
    Backend::instance('CVS'),
    Backend::instance('SVN'),
    Backend::instance('System')
);
$processor->process();

// Remove PID file when finished.
unlink($pathToPidFile);

?>
