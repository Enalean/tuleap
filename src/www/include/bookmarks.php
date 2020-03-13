<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
function bookmark_add($bookmark_url, $bookmark_title = "")
{
    if (!$bookmark_title) {
        $bookmark_title = $bookmark_url;
    }
    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
    $result = db_query("INSERT into user_bookmarks (user_id, bookmark_url, "
    . "bookmark_title) values ('" . $db_escaped_user_id . "', '" . db_es($bookmark_url) . "', "
    . "'" . db_es($bookmark_title) . "');");
    if (!$result) {
        echo db_error();
    }
        // Return bookmark ID
        $result = db_query("SELECT bookmark_id from user_bookmarks where user_id=" . $db_escaped_user_id .
                           " AND bookmark_url='" . db_es($bookmark_url) . "' AND bookmark_title='" . db_es($bookmark_title) . "'");
        return db_result($result, 0, "bookmark_id");
}

function bookmark_edit($bookmark_id, $bookmark_url, $bookmark_title)
{
    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
    db_query("UPDATE user_bookmarks SET bookmark_url='" . db_es($bookmark_url) . "', "
    . "bookmark_title='" . db_es($bookmark_title) . "' where bookmark_id='" . db_es($bookmark_id) . "' AND user_id='" . $db_escaped_user_id . "'");
}

function bookmark_delete($bookmark_id)
{
    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
    db_query("DELETE from user_bookmarks WHERE bookmark_id='" . db_es($bookmark_id) . "' "
    . "and user_id='" . $db_escaped_user_id . "'");
}
