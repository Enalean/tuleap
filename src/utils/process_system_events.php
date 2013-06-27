<?php
/**
 * Copyright (c) Enalean SAS, 2013. All rights reserved
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

require_once 'pre.php';
require_once 'common/system_event/SystemEventProcessorMutex.class.php';

if (isset($argv[1]) && $argv[1] == SystemEvent::OWNER_APP) {
    require_once 'common/system_event/SystemEventProcessor_ApplicationOwner.class.php';
    $processor = new SystemEventProcessor_ApplicationOwner(
        $system_event_manager,
        new SystemEventDao(),
        new BackendLogger()
    );
} else {
    require_once 'common/system_event/SystemEventProcessor_Root.class.php';
    $processor = new SystemEventProcessor_Root(
        $system_event_manager,
        new SystemEventDao(),
        new BackendLogger(),
        Backend::instance('Aliases'),
        Backend::instance('CVS'),
        Backend::instance('SVN'),
        Backend::instance('System')
    );
}

$mutex = new SystemEventProcessorMutex($processor);
$mutex->execute();

?>
