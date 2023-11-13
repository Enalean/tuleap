<?php
/**
 * Copyright (c) Enalean, 2016-Present. All rights reserved
 * Copyright 1999-2000 (c) The SourceForge Crew
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

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../forum/forum_utils.php';

$request = HTTPRequest::instance();
$HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle($Language->getText('my_monitored_forum', 'title')));
print "<H3>" . $Language->getText('my_monitored_forum', 'title') . "</H3>\n";
if (user_isloggedin()) {
    /*
        User obviously has to be logged in to monitor
        a thread
    */

    $vForumId = new Valid_UInt('forum_id');
    $vForumId->required();
    if ($request->valid($vForumId)) {
        $forum_id = $request->get('forum_id');

        $user_id = UserManager::instance()->getCurrentUser()->getId();

        $forum_monitor_error = false;
        if (user_monitor_forum($forum_id, $user_id)) {
            // If already monitored then stop monitoring
            forum_delete_monitor($forum_id, $user_id);
            print "<p>" . $Language->getText('my_monitored_forum', 'stop_monitoring') .
            "<P><A HREF=\"/my/\">[" . $Language->getText('global', 'back_home') . "]</A>";
        } else {
            // Not yet monitored so add it
            $forum_monitor_error = ! forum_add_monitor($forum_id, $user_id);
        }
    } else {
        forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get(_('Choose a forum First'))
            ->build());
        echo '
			<H1>' . _('Error - choose a forum first') . '</H1>';
        forum_footer();
    }
} else {
    exit_not_logged_in();
}
