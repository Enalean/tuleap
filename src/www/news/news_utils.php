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


function news_header(array $params)
{
    global $HTML,$group_id,$news_name,$news_id,$Language;

    \Tuleap\Project\ServiceInstrumentation::increment('news');

    $params['toptab']  = 'news';
    $params['project'] = ProjectManager::instance()->getProjectById((int) $group_id);

    if (isset($params['project_id'])) {
        $group_id = $params['project_id'];
    }

    $GLOBALS['HTML']->addBreadcrumbs([
        [
            'title' => $Language->getText('news_index', 'news'),
            'url' => '/news/?group_id=' . urlencode($group_id),
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
    if (! isset($params['pv']) || ! $params['pv']) {
        $purifier = Codendi_HTMLPurifier::instance();
        echo '<P><B>';
        // 'Admin' tab is only displayed if the user is News admin or project admin
        if ($group_id) {
            if (user_ismember($group_id, 'A') || user_ismember($group_id, 'N2')) {
                echo '<A HREF="/news/submit.php?group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('news_utils', 'submit_news') . '</A> | <A HREF="/news/admin/?group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('news_utils', 'admin') . '</A>';
            } elseif (user_ismember($group_id, 'A') || user_ismember($group_id, 'N1')) {
              // 'Submit News' tab is only displayed if the user is News writer, or project admin
                echo '<A HREF="/news/submit.php?group_id=' . $purifier->purify(urlencode($group_id)) . '">' . $Language->getText('news_utils', 'submit_news') . '</A>';
            }
            if (user_ismember($group_id, 'A') || user_ismember($group_id, 'N2') || user_ismember($group_id, 'N1')) {
                if (isset($params['help'])) {
                    echo ' | ';
                }
            }
        }
        if (isset($params['help'])) {
            echo help_button($params['help'], $Language->getText('global', 'help'));
        }
        echo '</b><P>';
    }
}

function news_footer($params)
{
    site_project_footer($params);
}

function news_show_latest($group_id = '', $limit = 10, $show_projectname = true, $allow_submit = true, $hide_nb_comments = false, $tail_headlines = 0)
{
    global $Language;
    $sys_news_group = ForgeConfig::get('sys_news_group');

    $return = "";
    if (! $group_id) {
        $group_id = $sys_news_group;
    }

    /*
    Show a simple list of the latest news items with a link to the forum
    */

    if ($group_id != $sys_news_group) {
        $wclause = "news_bytes.group_id = " . db_ei($group_id) . " AND news_bytes.is_approved < 4";
    } else {
        $wclause = 'news_bytes.is_approved = 1';
    }

    $sql = "SELECT `groups`.group_name,
                    `groups`.unix_group_name,
                    news_bytes.submitted_by,
                    news_bytes.forum_id,
                    news_bytes.summary,
                    news_bytes.date,
                    news_bytes.details,
                    count(forum.msg_id) AS num_comments
            FROM news_bytes
                INNER JOIN `groups` ON (news_bytes.group_id = `groups`.group_id)
                LEFT JOIN forum ON (forum.group_forum_id = news_bytes.forum_id)
            WHERE $wclause
              AND `groups`.status = 'A'
            GROUP BY news_bytes.forum_id
            ORDER BY date DESC LIMIT " . db_ei($limit + $tail_headlines);

    $result = db_query($sql);
    $rows   = db_numrows($result);

    if (! $result || $rows < 1) {
        $return .= '<b>' . $Language->getText('news_utils', 'no_news_item_found') . '</b>';
    } else {
        $news_item_displayed = false;
        while ($data = db_fetch_array($result)) {
            //check if the news is private (project members) or public (registered users)
            $forum_id = $data['forum_id'];
            if (news_check_permission($forum_id, $group_id)) {
                $return .= news_fetch_a_news_summary_block($data, $group_id, $limit, $show_projectname, $hide_nb_comments);

                if ($limit == 1 && $tail_headlines) {
                    $return .= '<ul class="unstyled">';
                }
                if ($limit) {
                    $limit--;
                }
                $news_item_displayed = true;
            }
        }
        if (! $news_item_displayed) {
            $return .= '<b>' . $Language->getText('news_utils', 'no_news_item_found') . '</b>';
        }
    }
    $purifier = Codendi_HTMLPurifier::instance();
    if ($group_id != $sys_news_group) {
        $archive_url = '/news/?group_id=' . $purifier->purify(urlencode($group_id));
    } else {
        $archive_url = '/news/';
    }

    if ($tail_headlines) {
        $return .= '</ul>' . "\n";
    }

    $return .= '<div align="center">
                    <a href="' . $archive_url . '">[' . $Language->getText('news_utils', 'news_archive') . ']</a></div>';

    if ($allow_submit && $group_id != $sys_news_group) {
        //you can only submit news from a project now
        //you used to be able to submit general news
        $return .= '<div align="center">
            <A HREF="/news/submit.php?group_id=' . $purifier->purify(urlencode($group_id)) . '">
                <FONT SIZE="-1">[' . $Language->getText('news_utils', 'submit_news') . ']</FONT>
            </A>
        </div>';
    }

    return $return;
}

function news_fetch_a_news_summary_block($data, $group_id, $limit, $show_projectname, $hide_nb_comments)
{
    global $Language;
    $purifier = Codendi_HTMLPurifier::instance();
    $uh       = new UserHelper();
    $html     = '';
    $arr      = explode("\n", $data['details']);
    if ((strlen($arr[0]) < 200) && isset($arr[1]) && isset($arr[2]) && (strlen($arr[1] . $arr[2]) < 300) && (strlen($arr[2]) > 5)) {
        $details = $purifier->purify($arr[0] . "\n" . $arr[1] . "\n" . $arr[2], CODENDI_PURIFIER_BASIC, $group_id);
    } else {
        $details = $purifier->purify($arr[0], CODENDI_PURIFIER_BASIC, $group_id);
    }

    $user = UserManager::instance()->getCurrentUser();

    $proj_name = '';
    if ($show_projectname && $limit) {
        //show the project name
        $proj_name = ' &middot; <a href="/projects/' . $purifier->purify(urlencode(strtolower($data['unix_group_name']))) . '/">' . $purifier->purify($data['group_name']) . '</a>';
    }

    $forum_url = '/forum/forum.php?forum_id=' . $purifier->purify(urlencode($data['forum_id']));
    if (! $limit) {
        $html .= '<li><span class="news_summary"><a href="' . $purifier->purify($forum_url) . '">' . $purifier->purify($data['summary']) . '</a></span> ';
        $html .= '<small><span class="news_date">' . DateHelper::relativeDateInlineContext((int) $data['date'], $user) . '</span></small></li>';
    } else {
        $comments_txt = '';
        if (! $hide_nb_comments) {
            $num_comments  = (int) $data['num_comments'];
            $comments_txt .= ' <a href="' . $purifier->purify($forum_url) . '">(' . $purifier->purify($num_comments) . ' ';
            if ($num_comments == 1) {
                $comments_txt .= $Language->getText('news_utils', 'comment');
            } else {
                $comments_txt .= $Language->getText('news_utils', 'comments');
            }
            $comments_txt .= ')</a>';
        }

        $html .= '<div class="news">';
        $html .= '<span class="news_summary"><a href="' . $purifier->purify($forum_url) . '"><h4>' . $purifier->purify($data['summary']) . '</h4></a></span>';

        $html .= '<blockquote>';
        $html .= '<div>' . $details . '</div>';
        $html .= '<small>
                    <span class="news_author">' . $uh->getLinkOnUserFromUserId($data['submitted_by']) . '</span>
                    <span class="news_date">' . DateHelper::relativeDateInlineContext((int) $data['date'], $user) . '</span>' .
                    $comments_txt .
                    $proj_name .
                    '</small>';
        $html .= '</blockquote>';
        $html .= '<hr width="100%" size="1" noshade>';
        $html .= '</div>';
    }
    return $html;
}

function get_news_name($id)
{
    /*
        Takes an ID and returns the corresponding forum name
    */
    $sql    = "SELECT summary FROM news_bytes WHERE id=" . db_ei($id);
    $result = db_query($sql);
    if (! $result || db_numrows($result) < 1) {
        return "Not Found";
    } else {
        return db_result($result, 0, 'summary');
    }
}

function get_news_name_from_forum_id($id)
{
    /*
        Takes an ID and returns the corresponding forum name
    */
    $sql    = "SELECT summary FROM news_bytes WHERE forum_id=" . db_ei($id);
    $result = db_query($sql);
    if (! $result || db_numrows($result) < 1) {
        return "Not Found";
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
    $sql                = "INSERT INTO news_bytes (group_id,submitted_by,is_approved,date,forum_id,summary,details)
          VALUES (" . db_ei($group_id) . ", '" . $db_escaped_user_id . "', " . db_ei($promote_news) . ", '" . time() . "',
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
        // extract cross references
        $reference_manager = ReferenceManager::instance();
        $reference_manager->extractCrossRef($summary, $new_id, ReferenceManager::REFERENCE_NATURE_NEWS, $group_id);
        $reference_manager->extractCrossRef($details, $new_id, ReferenceManager::REFERENCE_NATURE_NEWS, $group_id);
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
        $sql = "SELECT g.access FROM news_bytes AS n INNER JOIN `groups` AS g USING(group_id) WHERE n.forum_id = " . db_ei($forum_id);
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
    $body .= \Tuleap\ServerHostname::HTTPSUrl() . "/news/admin/?approve=1&id=" . $news_bytes_id . ForgeConfig::get('sys_lf');
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
