<?php
// Copyright (c) Enalean, 2017-Present. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net

function viewvc_utils_track_browsing($group_id, $type)
{
    $query_string = getStringFromServer('QUERY_STRING');
    $request_uri  = getStringFromServer('REQUEST_URI');

    if (
        strpos($query_string, "view=markup") !== false ||
        strpos($query_string, "view=auto") !== false ||
        strpos($request_uri, "*checkout*") !== false ||
        strpos($query_string, "annotate=") !== false
    ) {
        if ($type == 'svn') {
            $browse_column = 'svn_browse';
            $table         = 'group_svn_full_history';
        }

        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
        $year               = date("Y");
        $mon                = date("m");
        $day                = date("d");
        $db_day             = $year . $mon . $day;

        $sql = "SELECT " . $browse_column . " FROM " . $table . " WHERE group_id = " . db_ei($group_id) . " AND user_id = " . $db_escaped_user_id . " AND day = '" . $db_day . "'";
        $res = db_query($sql);
        if (db_numrows($res) > 0) {
            db_query("UPDATE " . $table . " SET " . $browse_column . "=" . $browse_column . "+1 WHERE group_id = " . db_ei($group_id) . " AND user_id = " . $db_escaped_user_id . " AND day = '" . $db_day . "'");
        } else {
            db_query("INSERT INTO " . $table . " (group_id,user_id,day," . $browse_column . ") VALUES (" . db_ei($group_id) . "," . $db_escaped_user_id . ",'" . $db_day . "',1)");
        }
    }
}
