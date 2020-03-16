<?php
// Copyright 2014-Present (c) Enalean SAS
// This file is part of Tuleap
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../mail_utils.php';

$sys_lists_domain = ForgeConfig::get('sys_lists_domain');
if ($sys_lists_domain == 'lists.%sys_default_domain%') {
    $sys_lists_domain = ForgeConfig::get('sys_lists_host');
}

$pm = ProjectManager::instance();
if ($group_id && user_ismember($group_id, 'A')) {
    $list_server = get_list_server_url();

    if ($request->existAndNonEmpty('post_changes')) {
        /*
          Update the DB to reflect the changes
         */

        if ($request->existAndNonEmpty('add_list')) {
            $list_password = substr(md5($GLOBALS['session_hash'] . time() . rand(0, 40000)), 0, 16);
            $list_name = $request->getValidated('list_name', 'string', '');
            if (!$list_name || strlen($list_name) < ForgeConfig::get('sys_lists_name_min_length')) {
                exit_error($Language->getText('global', 'error'), $Language->getText('mail_admin_index', 'provide_correct_list_name'));
            }
            if (! preg_match('/(^([a-zA-Z\_0-9\.-]*))$/', $list_name)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('mail_admin_index', 'list_name_unauthorized_char'));
            }
            if (user_is_super_user()) {
                $new_list_name = strtolower($list_name);
            } else {
                $new_list_name = ForgeConfig::get('sys_lists_prefix') . strtolower($pm->getProject($group_id)->getUnixName() . '-' . $list_name) . ForgeConfig::get('sys_lists_suffix');
            }

            //see if that's a valid email address
            if (validate_email($new_list_name . '@' . $sys_lists_domain)) {
                $result = db_query("SELECT * FROM mail_group_list WHERE lower(list_name)='" . db_es($new_list_name) . "'");

                if (db_numrows($result) > 0) {
                    $feedback .= ' ' . $Language->getText('mail_admin_index', 'list_exists_err') . ' ';
                } else {
                    $group_id = db_ei($group_id);
                    $is_public = db_ei($request->getValidated('is_public', 'int', 0));
                    $description = db_es(htmlspecialchars($request->getValidated('description', 'string', '')));
                    $new_list_name = db_es($new_list_name);
                    $list_password = db_es($list_password);
                    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
                    $sql = "INSERT INTO mail_group_list
                                            (group_id,list_name,is_public,password,list_admin,status,description) VALUES (
                                            $group_id,
                                            '$new_list_name',
                                            $is_public,
                                            '$list_password',
                                            $db_escaped_user_id,
                                            1,
                                            '$description')";


                    $result = db_query($sql);
                    $group_list_id = db_insertid($result);

                    if (!$result) {
                        $feedback .= ' ' . $Language->getText('mail_admin_index', 'add_list_err') . ' ';
                        echo db_error();
                    } else {
                        $feedback .= ' ' . $Language->getText('mail_admin_index', 'list_added') . ' ';
                    }

                    // Raise an event
                    EventManager::instance()->processEvent('mail_list_create', array('group_list_id' => $group_list_id,));

                    // get email addr
                    $res_email = db_query("SELECT email FROM user WHERE user_id='" . $db_escaped_user_id . "'");
                    if (db_numrows($res_email) < 1) {
                        exit_error($Language->getText('mail_admin_index', 'invalid_userid'), $Language->getText('mail_admin_index', 'does_not_compute'));
                    }
                    $row_email = db_fetch_array($res_email);

                    // mail password to admin
                    $message = $Language->getText('mail_admin_index', 'list_create_explain', array($GLOBALS['sys_name'], $new_list_name . '@' . $sys_lists_domain, $list_server . "/mailman/listinfo/$new_list_name", $list_server . "/mailman/admin/$new_list_name", $list_password));

                    $hdrs = "From: " . $GLOBALS['sys_email_admin'] . $GLOBALS['sys_lf'];
                    $hdrs .= 'Content-type: text/plain; charset=utf-8' . $GLOBALS['sys_lf'];

                    mail($row_email['email'], $GLOBALS['sys_name'] . " " . $Language->getText('mail_admin_index', 'new_mail_list'), $message, $hdrs);

                    $feedback .= " " . $Language->getText('mail_admin_index', 'mail_sent_to', $row_email['email']) . " ";
                }
            } else {
                $feedback .= ' ' . $Language->getText('mail_admin_index', 'invalid_list_name') . ' ';
            }
        } elseif ($request->existAndNonEmpty('change_status')) {
            /*
              Change a list to public/private and description
             */
            $is_public = $request->getValidated('is_public', 'int', 0);
            $description = $request->getValidated('description', 'string', '');
            $group_list_id = $request->getValidated('group_list_id', 'int', 0);
            $sql = "UPDATE mail_group_list SET is_public='" . db_ei($is_public) . "', " .
                    "description='" . db_es(htmlspecialchars($description)) . "' " .
                    "WHERE group_list_id='" . db_ei($group_list_id) . "' AND group_id='" . db_ei($group_id) . "'";
            $result = db_query($sql);
            if (!$result || db_affected_rows($result) < 1) {
                $feedback .= ' ' . $Language->getText('mail_admin_index', 'upate_status_err') . ' ';
                echo db_error();
            } else {
                if ($is_public == 9) {
                    // List deleted: raise event
                    EventManager::instance()->processEvent('mail_list_delete', array('group_list_id' => $group_list_id,));
                }
                $feedback .= ' ' . $Language->getText('mail_admin_index', 'status_update_success') . ' ';
            }
        }
    }

    if ($request->existAndNonEmpty('add_list')) {
        /*
          Show the form for adding mailing list
         */
        mail_header_admin(array('title' => $Language->getText('mail_admin_index', 'add_a_mail_list'),
            'help' => 'collaboration.html#creation'));

        echo '
            <H3>' . $Language->getText('mail_admin_index', 'add_a_mail_list') . '</H3>';
        include($Language->getContent('mail/addlist_intro'));

        $result = db_query("SELECT list_name FROM mail_group_list WHERE group_id='$group_id'");
        ShowResultSet($result, $Language->getText('mail_admin_index', 'existing_mail_list'), false);

        echo '<P>
            <FORM METHOD="POST" ACTION="?">
            <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
            <INPUT TYPE="HIDDEN" NAME="add_list" VALUE="y">
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">
            <B>' . $Language->getText('mail_admin_index', 'mail_list_name') . ':</B><BR>';

        // if the user is super user then he has the right to choose the
        // full mailing list name
        if (user_is_super_user()) {
            echo '<INPUT TYPE="TEXT" NAME="list_name"
            VALUE="' . ForgeConfig::get('sys_lists_prefix') . $pm->getProject($group_id)->getUnixName() . '-xxxxx" SIZE="15" MAXLENGTH="20" CLASS="textfield_small">@' . $sys_lists_domain . '</B><BR>';
        } else {
            echo '<B>' . ForgeConfig::get('sys_lists_prefix') . $pm->getProject($group_id)->getUnixName() . '-<INPUT TYPE="TEXT" NAME="list_name" VALUE="" SIZE="15" MAXLENGTH="20" CLASS="textfield_small">@' . $sys_lists_domain . '</B><BR>';
        }
        echo '    <P>
            <B>' . $Language->getText('mail_admin_index', 'is_public') . ' </B>' . $Language->getText('mail_admin_index', 'public_explain') . '<BR>
            <INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> ' . $Language->getText('global', 'yes') . '<BR>
            <INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> ' . $Language->getText('global', 'no') . '<P>
            <B>' . $Language->getText('mail_admin_index', 'desc') . ':</B><BR>
            <INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="60" MAXLENGTH="160"><BR>
            <P>
            <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $Language->getText('mail_admin_index', 'add_this_list') . '">
            </FORM>';

        mail_footer(array());
    } elseif ($request->existAndNonEmpty('change_status')) {
        /*
          Change a forum to public/private
         */
        mail_header_admin(array('title' => $Language->getText('mail_admin_index', 'update_mail_list'),
            'help' => 'collaboration.html#creation'));

        $sql = "SELECT list_name,group_list_id,is_public,description " .
                "FROM mail_group_list " .
                "WHERE group_id='" . db_ei($group_id) . "'";
        $result = db_query($sql);
        $rows = db_numrows($result);

        if (!$result || $rows < 1) {
            echo '
                <H2>' . $Language->getText('mail_admin_index', 'no_list_found') . '</H2>
                <P>
                ' . $Language->getText('mail_admin_index', 'none_found_for_project');
            echo db_error();
        } else {
            echo '
            <H2>' . $Language->getText('mail_admin_index', 'update_mail_list') . '</H2>
            <P>
            ' . $Language->getText('mail_admin_index', 'admin_lists_here', $GLOBALS['sys_name']) . '<P>';

            $title_arr = array();
            $title_arr[] = $Language->getText('mail_admin_index', 'list');
            $title_arr[] = $Language->getText('global', 'status');
            $title_arr[] = $Language->getText('mail_admin_index', 'update');
            $title_arr[] = $Language->getText('mail_admin_index', 'list_admin');

            echo html_build_list_table_top($title_arr);

            for ($i = 0; $i < $rows; $i++) {
                echo '
                    <TR class="' . util_get_alt_row_color($i) . '"><TD><B>' . db_result($result, $i, 'list_name') . '</B></TD>';
                echo '
                    <FORM ACTION="?" METHOD="POST">
                    <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
                    <INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
                    <INPUT TYPE="HIDDEN" NAME="group_list_id" VALUE="' . db_result($result, $i, 'group_list_id') . '">
                    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">
                    <TD>
                        <FONT SIZE="-1">
                        <INPUT TYPE="RADIO" NAME="is_public" VALUE="1"' . ((db_result($result, $i, 'is_public') == '1') ? ' CHECKED' : '') . '> ' . $Language->getText('mail_admin_index', 'public') . '<BR>
                        <INPUT TYPE="RADIO" NAME="is_public" VALUE="0"' . ((db_result($result, $i, 'is_public') == '0') ? ' CHECKED' : '') . '> ' . $Language->getText('mail_admin_index', 'private') . '<BR>
                        <INPUT TYPE="RADIO" NAME="is_public" VALUE="9"' . ((db_result($result, $i, 'is_public') == '9') ? ' CHECKED' : '') . '> ' . $Language->getText('mail_admin_index', 'delete') . '<BR>
                    </TD><TD>
                        <FONT SIZE="-1">
                        <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $Language->getText('global', 'btn_update') . '">
                    </TD>
                    <TD><A href="' . $list_server . '/mailman/admin/'
                . db_result($result, $i, 'list_name') . '">[' . $Language->getText('mail_admin_index', 'admin_in_gnu') . ']</A>
                       </TD></TR>
                       <TR class="' . util_get_alt_row_color($i) . '"><TD COLSPAN="4">
                                        ' . $Language->getText('mail_admin_index', 'desc') . ':
                        <INPUT TYPE="TEXT" NAME="description" VALUE="' .
                db_result($result, $i, 'description') . '" SIZE="70" MAXLENGTH="160"><BR>
                    </TD></TR></FORM>';
            }
            echo '</TABLE>';
        }

        mail_footer(array());
    } else {
        /*
          Show main page for choosing
          either moderotor or delete
         */
        mail_header_admin(array('title' => $Language->getText('mail_admin_index', 'mail_list_admin'),
            'help' => 'collaboration.html#mailing-lists'));

        echo '
            <H2>' . $Language->getText('mail_admin_index', 'mail_list_admin') . '</H2>
            <h3>
            <A HREF="?group_id=' . $group_id . '&add_list=1">' . $Language->getText('mail_admin_index', 'add_mail_list') . '</A></h3>
                                                      <p>' . $Language->getText('mail_admin_index', 'create_new_mail_lists') . '
            <h3><A HREF="?group_id=' . $group_id . '&change_status=1">' . $Language->getText('mail_admin_index', 'admin_update_lists') . '</A></h3>
                                                      <p>' . $Language->getText('mail_admin_index', 'manage_mail');
        mail_footer(array());
    }
} else {
    /*
      Not logged in or insufficient privileges
     */
    if (!$group_id) {
        exit_no_group();
    } else {
        exit_permission_denied();
    }
}
