<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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


function mail_header(array $params, PFUser $user): void
{
    global $group_id, $Language;

    \Tuleap\Project\ServiceInstrumentation::increment('mailinglists');

    //required for site_project_header
    $params['group']  = $group_id;
    $params['toptab'] = 'mail';

    $pm      = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    if (! $project->usesMail()) {
        exit_error($Language->getText('global', 'error'), _('This Project Has Turned Off Mailing Lists'));
    }

    $service = $project->getService(Service::ML);
    assert($service instanceof \Tuleap\MailingList\ServiceMailingList);

    $service->displayMailingListHeader($user, $params['title']);
}

function mail_footer($params)
{
    site_project_footer($params);
}

// Checks if the mailing-list (list_id) is public (return 1) or private (return 0)
function mail_is_list_public($list)
{
    $sql = sprintf(
        'SELECT is_public FROM mail_group_list' .
                      ' WHERE group_list_id = "%d"',
        db_ei($list)
    );
    $res = db_query($sql);

    return db_result($res, 0, 'is_public');
}

//Checks if a mailing-list (list_id) exist and is active
function mail_is_list_active($list)
{
    $sql = sprintf(
        'SELECT status' .
                    ' FROM mail_group_list' .
                    ' WHERE group_list_id = "%d"',
        db_ei($list)
    );
    $res = db_query($sql);
    if (db_numrows($res) < 1) {
        return false;
    } else {
        $status = db_result($res, 0, 'status');
        if ($status <> 1) {
            return false;
        } else {
            return true;
        }
    }
}

// Gets mailing-list name from list id
function mail_get_listname_from_list_id($list_id)
{
    $sql = sprintf(
        'SELECT list_name' .
                    ' FROM mail_group_list' .
                    ' WHERE group_list_id = %d',
        db_ei($list_id)
    );
    $res = db_query($sql);
    return db_result($res, 0, 'list_name');
}
