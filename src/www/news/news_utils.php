<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

/*
    News System
    By Tim Perdue, Sourceforge, 12/99
*/

/**
 * Status of news (a.k.a meaning of is_approved field):
 * 0 => normal (nothing special, created, visible, no promtion asked)
 * 1 => promoted on server homepage
 * 2 => rejected for promotion on server homepage (by siteadmin)
 * 3 => pending for promotion (on server homepage)
 * 4 => deleted
 *
 * Status public/private is managed with PERMISSION
 *
 */

require_once __DIR__ . '/../project/admin/permissions.php';
require_once __DIR__ . '/../project/admin/ugroup_utils.php';
require_once __DIR__ . '/../forum/forum_utils.php';


function news_header(Tuleap\Layout\HeaderConfiguration $params)
{
    global $HTML,$group_id, $Language;

    \Tuleap\Project\ServiceInstrumentation::increment('news');

    if ($params->in_project) {
        $group_id = $params->in_project->project->getGroupId();
    }

    $GLOBALS['HTML']->addBreadcrumbs([
        [
            'title' => $Language->getText('news_index', 'news'),
            'url' => '/news/?group_id=' . urlencode((string) $group_id),
        ],
    ]);

    /*
        Show horizontal links
    */
    if ($group_id && ($group_id != ForgeConfig::get('sys_news_group'))) {
        site_project_header(ProjectManager::instance()->getProjectById((int) $group_id), $params);
    } else {
        $HTML->header($params);
        echo '
			<H2>' . ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . ' <A HREF="/news/">' . $Language->getText('news_index', 'news') . '</A></H2>';
    }
    if (! $params->printer_version) {
        $purifier = Codendi_HTMLPurifier::instance();
        echo '<P><B>';
        // 'Admin' tab is only displayed if the user is News admin or project admin
        if ($group_id) {
            if (user_ismember($group_id, 'A') || user_ismember($group_id, 'N2')) {
                echo '<A HREF="/news/submit.php?group_id=' . $purifier->purify(urlencode((string) $group_id)) . '">' . $Language->getText('news_utils', 'submit_news') . '</A> | <A HREF="/news/admin/?group_id=' . $purifier->purify(urlencode((string) $group_id)) . '">' . $Language->getText('news_utils', 'admin') . '</A>';
            } elseif (user_ismember($group_id, 'A') || user_ismember($group_id, 'N1')) {
              // 'Submit News' tab is only displayed if the user is News writer, or project admin
                echo '<A HREF="/news/submit.php?group_id=' . $purifier->purify(urlencode((string) $group_id)) . '">' . $Language->getText('news_utils', 'submit_news') . '</A>';
            }
        }
        echo '</b><P>';
    }
}

function news_footer($params)
{
    site_project_footer($params);
}

function get_news_name_from_forum_id($id)
{
    /*
        Takes an ID and returns the corresponding forum name
    */
    $sql    = 'SELECT summary FROM news_bytes WHERE forum_id=' . db_ei($id);
    $result = db_query($sql);
    if (! $result || db_numrows($result) < 1) {
        return 'Not Found';
    } else {
        return db_result($result, 0, 'summary');
    }
}

function news_submit($group_id, $summary, $details, $private_news, $send_news_to, $promote_news = 0)
{
    /*
        Takes Summary and Details, and submit the corresponding news, in the right project, with the right permissions
    */

    $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());
    $new_id             = forum_create_forum(ForgeConfig::get('sys_news_group'), $summary, 1, 0, '', $need_feedback = false);
    $sql                = 'INSERT INTO news_bytes (group_id,submitted_by,is_approved,date,forum_id,summary,details)
          VALUES (' . db_ei($group_id) . ", '" . $db_escaped_user_id . "', " . db_ei($promote_news) . ", '" . time() . "',
                 '$new_id', '" . db_es($summary) . "', '" . db_es($details) . "')";
    $result             = db_query($sql);

    if (! $result) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('news_submit', 'insert_err'));
    } else {
        // retrieve the id of the news
        $news_bytes_id = db_insertid($result);
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('news_submit', 'news_added'));
         // set permissions on this piece of news
        if ($private_news) {
            news_insert_permissions($new_id, $group_id);
        }
        if ($promote_news == 3) {
            // if the news is requested to be promoted, we notify the site admin about it
            news_notify_promotion_request($group_id, $news_bytes_id, $summary, $details);
        }

        if ($send_news_to) {
            news_send_to_ugroups($send_news_to, $summary, $details, $group_id);
        }
    }
}

function news_check_permission($forum_id, $group_id)
{
    /*
        Takes a forum_id and checks if user is authorized to read the piece of news associated to this forum_id
    */

    //cast  input

    if ($group_id == ForgeConfig::get('sys_news_group')) {
        //search for the real group_id of the news
        $sql = 'SELECT g.access FROM news_bytes AS n INNER JOIN `groups` AS g USING(group_id) WHERE n.forum_id = ' . db_ei($forum_id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $row = db_fetch_array($res);
            //see if it is public to continue permissions check
            if (in_array($row['access'], [Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED], true)) {
                return false;
            }
        }
    }
    $user_id = UserManager::instance()->getCurrentUser()->getId();
    if (((permission_exist('NEWS_READ', $forum_id)) && (permission_is_authorized('NEWS_READ', $forum_id, $user_id, $group_id))) || (! permission_exist('NEWS_READ', $forum_id))) {
        return true;
    } else {
        return false;
    }
}

/**
 * insert for this forum_id a news_read permission for project members only
 */
function news_insert_permissions($forum_id, $group_id)
{
    global $Language,$UGROUP_PROJECT_MEMBERS;

    //We force permission if user is project admin... beurk
    $force = user_ismember($group_id, 'A');

    if (permission_add_ugroup($group_id, 'NEWS_READ', $forum_id, $UGROUP_PROJECT_MEMBERS, $force)) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('news_submit', 'news_perm_create_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('news_submit', 'insert_err'));
    }
}

function news_update_permissions($forum_id, $is_private, $group_id)
{
    global $Language,$UGROUP_PROJECT_MEMBERS;

    /*
        Takes forum_id and permission, and updates the permission of the corresponding entry in 'permissions' table
    */

    if ($is_private == 3) {
        permission_clear_all($group_id, 'NEWS_READ', $forum_id, false);
        if (permission_add_ugroup($group_id, 'NEWS_READ', $forum_id, $UGROUP_PROJECT_MEMBERS)) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('news_submit', 'news_perm_update_success'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index', 'update_err'));
        }
    } else {
        if (permission_clear_all($group_id, 'NEWS_READ', $forum_id, false)) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('news_submit', 'news_perm_update_success'));
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('news_admin_index', 'update_err'));
        }
    }
}

function news_read_permissions($forum_id)
{
    /*
        Takes forum_id and reads the permission of the corresponding news. Returns a result set.
    */

    return permission_db_authorized_ugroups('NEWS_READ', $forum_id);
}

function news_notify_promotion_request($group_id, $news_bytes_id, $summary, $details)
{
    global $Language;

    $pm    = ProjectManager::instance();
    $group = $pm->getProject($group_id);
    // retrieve the user that submit the news
    $user = UserManager::instance()->getCurrentUser();

    $mail = new Codendi_Mail();
    $mail->setFrom(ForgeConfig::get('sys_noreply'));
    $mail->setTo(ForgeConfig::get('sys_email_admin'), true); // Don't invalidate admin email!
    $mail->setSubject($Language->getText('news_utils', 'news_request', [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)]));
    $body  = '';
    $body .= $Language->getText('news_utils', 'news_request_mail_intro', [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)]) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf');
    $body .= $Language->getText('news_utils', 'news_request_mail_project', [$group->getPublicName(), $group->getUnixName()]) . ForgeConfig::get('sys_lf');
    $body .= $Language->getText('news_utils', 'news_request_mail_submitted_by', [$user->getUserName()]) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf');
    $body .= $Language->getText('news_utils', 'news_request_mail_summary', [$summary]) . ForgeConfig::get('sys_lf');
    $body .= $Language->getText('news_utils', 'news_request_mail_details', [$details]) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf');
    $body .= $Language->getText('news_utils', 'news_request_mail_approve_link') . ForgeConfig::get('sys_lf');
    $body .= \Tuleap\ServerHostname::HTTPSUrl() . '/news/admin/?approve=1&id=' . $news_bytes_id . ForgeConfig::get('sys_lf');
    $mail->setBodyText($body);

    $is_sent = $mail->send();
    if ($is_sent) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('news_utils', 'news_request_sent'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('news_utils', 'news_request_not_sent'));
    }
}

function news_send_to_ugroups($ugroups, $summary, $details, $group_id)
{
    $hp             = Codendi_HTMLPurifier::instance();
    $pm             = ProjectManager::instance();
    $project        = $pm->getProject($group_id);
    $user           = HTTPRequest::instance()->getCurrentUser();
    $ugroup_manager = new UGroupManager();

    $html_body  = '<h1>' . $hp->purify($summary, CODENDI_PURIFIER_BASIC) . '</h1>';
    $html_body .= '<p>' . $hp->purify($details, CODENDI_PURIFIER_BASIC) . '</p>';

    $users = [];
    foreach ($ugroups as $ugroup_id) {
        $ugroup = $ugroup_manager->getUGroupWithMembers($project, $ugroup_id);
        foreach ($ugroup->getMembers() as $member) {
            $users[] = $member;
        }
    }

    $massmail_sender = new MassmailSender();
    $is_sent         = $massmail_sender->sendMassmail($project, $user, $summary, $html_body, $users);

    if ($is_sent) {
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('news_utils', 'news_sent'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('news_utils', 'news_not_sent'));
    }
}

function news_fetch_ugroups($project)
{
    $ugroup_manager   = new UGroupManager();
    $hp               = Codendi_HTMLPurifier::instance();
    $excluded_ugroups = [
        ProjectUGroup::NONE,
        ProjectUGroup::ANONYMOUS,
        ProjectUGroup::REGISTERED,
        ProjectUGroup::TRACKER_ADMIN,
    ];

    $ugroups = $ugroup_manager->getUGroups($project, $excluded_ugroups);
    $html    = '';
    $html   .= '<select multiple="multiple" name="send_news_to[]">';

    foreach ($ugroups as $ugroup) {
        $html .= '<option value="' . $hp->purify($ugroup->getId()) . '">';
        $html .= $hp->purify($ugroup->getTranslatedName());
        $html .= '</option>';
    }
    $html .= '</select>';

    return $html;
}
