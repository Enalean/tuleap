<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net

function mail_header($params)
{
    global $group_id, $Language;

    \Tuleap\Project\ServiceInstrumentation::increment('mailinglists');

    //required for site_project_header
    $params['group'] = $group_id;
    $params['toptab'] = 'mail';

    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    if (!$project->usesMail()) {
        exit_error($Language->getText('global', 'error'), $Language->getText('mail_utils', 'mail_turned_off'));
    }

    site_project_header($params);
    echo '<P><B>';
    // admin link is only displayed if the user is a project administrator
    if (user_ismember($group_id, 'A')) {
        echo '<A HREF="/mail/admin/?group_id=' . $group_id . '">' . $Language->getText('mail_utils', 'admin') . '</A>';
        echo ' | ';
    }
    if ($params['help']) {
        echo help_button($params['help'], false, $Language->getText('global', 'help'));
    }
    echo '</B><P>';
}
function mail_header_admin($params)
{
    global $group_id, $Language;

    //required for site_project_header
    $params['group'] = $group_id;
    $params['toptab'] = 'mail';

    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    if (!$project->usesMail()) {
        exit_error($Language->getText('global', 'error'), $Language->getText('mail_utils', 'mail_turned_off'));
    }

    site_project_header($params);
    echo '
		<P><B><A HREF="/mail/admin/?group_id=' . $group_id . '">' . $Language->getText('mail_utils', 'admin') . '</A></B>
 | <B><A HREF="/mail/admin/?group_id=' . $group_id . '&add_list=1">' . $Language->getText('mail_utils', 'add_list') . '</A></B>
 | <B><A HREF="/mail/admin/?group_id=' . $group_id . '&change_status=1">' . $Language->getText('mail_utils', 'update_list') . '</A></B>
';
    if ($params['help']) {
        echo ' | <B>' . help_button($params['help'], false, $Language->getText('global', 'help')) . '</B>';
    }
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
        $list
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
        $list
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
        $list_id
    );
    $res = db_query($sql);
    return db_result($res, 0, 'list_name');
}
