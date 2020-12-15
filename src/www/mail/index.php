<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
require_once __DIR__ . '/../mail/mail_utils.php';


$pv = isset($pv) ? $pv : false;

function display_ml_details($group_id, $list_server, $result, $i)
{
    echo '<IMG SRC="' . util_get_image_theme("ic/cfolder15.png") . '" HEIGHT="13" WIDTH="15" BORDER="0">&nbsp;<b>' . db_result($result, $i, 'list_name') . '</b> [';
    $list_is_public = db_result($result, $i, 'is_public');
    $html_a = '';
    $em = EventManager::instance();
    $em->processEvent('browse_archives', ['html' => &$html_a,
                                               'group_list_id' => db_result($result, $i, 'group_list_id')
                                            ]);
    if ($html_a) {
        echo $html_a;
    } else {
        if ($list_is_public) {
            echo ' <A HREF="?group_id=' . $group_id . '&amp;action=pipermail&amp;id=' . db_result($result, $i, 'group_list_id') . '">' . _('Archives') . '</A>';
        } else {
            echo ' ' . _('Archives') . ': <A HREF="?group_id=' . $group_id . '&amp;action=pipermail&amp;id=' . db_result($result, $i, 'group_list_id') . '">' . _('public') . '</A>/<A HREF="?group_id=' . $group_id . '&amp;action=private&amp;id=' . db_result($result, $i, 'group_list_id') . '">' . _('private') . '</A>';
        }
    }

    echo ' | <A HREF="?group_id=' . $group_id . '&amp;action=listinfo&amp;id=' . db_result($result, $i, 'group_list_id') . '">' . _('(Un)Subscribe/Preferences') . '</A>)';
    echo ' | <A HREF="?group_id=' . $group_id . '&amp;action=admin&amp;id=' . db_result($result, $i, 'group_list_id') . '">' . _('ML Administration') . '</A>';
    echo ' ]<br>&nbsp;' .  db_result($result, $i, 'description') . '<p>';
}

$request = HTTPRequest::instance();
$valid_project_id = new Valid_GroupId();
$valid_project_id->required();
if (! $request->valid($valid_project_id)) {
    exit_no_group();
    exit();
}
$group_id = $request->get('group_id');
if ($group_id) {
    $list_server = get_list_server_url();

    $hp = Codendi_HTMLPurifier::instance();
    $pm = ProjectManager::instance();
    $params = ['title' => sprintf(_('Mailing Lists for %1$s'), $pm->getProject($group_id)->getPublicName()),
              'help' => 'collaboration.html#mailing-lists',
                  'pv'   => isset($pv) ? $pv : false];
    mail_header($params, $request->getCurrentUser());

    if (user_isloggedin() && user_ismember($group_id)) {
        $public_flag = '0,1';
    } else {
        $public_flag = '1';
    }
    if ($request->exist('action')) {
        if ($request->exist('id')) {
            $sql = "SELECT * FROM mail_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag) AND group_list_id = " . (int) $request->get('id');
            $result = db_query($sql);
            if (db_numrows($result)) {
                display_ml_details($group_id, $list_server, $result, 0);
                echo '<a href="?group_id=' . $group_id . '">Go back to mailing lists</a>';
                switch ($request->get('action')) {
                    case 'admin':
                    case 'listinfo':
                    case 'private':
                        $iframe_url = $list_server . '/mailman/' . $request->get('action') . '/' . db_result($result, 0, 'list_name') . '/';
                        break;
                    case 'pipermail':
                        $iframe_url = $list_server . '/pipermail/' . db_result($result, 0, 'list_name');
                        break;
                    default:
                        break;
                }
                if ($iframe_url) {
                    $GLOBALS['HTML']->iframe($iframe_url, ['class' => 'iframe_service', 'width' => '100%', 'height' => '650px']);
                }
            }
        }
    } else {
        $sql = "SELECT * FROM mail_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag)";

        $result = db_query($sql);

        $rows = db_numrows($result);


        if (! $result || $rows < 1) {
            $pm = ProjectManager::instance();
            echo '
                <H1>' . sprintf(_('No Lists found for %1$s'), $hp->purify($pm->getProject($group_id)->getPublicName())) . '</H1>';
            echo '
                <P>' . _('Project administrators use the admin link to request mailing lists.');
                    mail_footer(['pv'   => isset($pv) ? $pv : false]);
            exit;
        }

        if ($Language->hasText('mail_index', 'mail_list_via_gnu')) {
            echo '<p>' . $Language->getOverridableText('mail_index', 'mail_list_via_gnu') . '</p>';
        }

        if ($pv) {
            echo "<P>" . _('Choose a list to browse, search, and post messages.') . "<P>\n";
        } else {
            echo "<TABLE width='100%'><TR><TD>";
            echo "<P>" . _('Choose a list to browse, search, and post messages.') . "<P>\n";
            echo "</TD>";
            echo "<TD align='left'> ( <A HREF='?group_id=$group_id&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
            echo "</TR></TABLE>";
        }

        /*
            Put the result set (list of mailing lists for this group) into a column with folders
        */

        echo "<table WIDTH=\"100%\" border=0>\n" .
            "<TR><TD VALIGN=\"TOP\">\n";

        for ($j = 0; $j < $rows; $j++) {
            display_ml_details($group_id, $list_server, $result, $j);
        }
        echo '</TD></TR></TABLE>';
    }
} else {
    $params = ['title' => _('Choose a Group First'),
                  'help' => 'collaboration.html#mailing-lists',
                  'pv'   => $pv];
    mail_header($params, $request->getCurrentUser());
    echo '
		<H1>' . _('Error - choose a group first') . '</H1>';
}
mail_footer(['pv'   => $pv]);
