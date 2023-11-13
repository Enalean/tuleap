<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
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

use Tuleap\Forum\MessageNotFoundException;
use Tuleap\Forum\MessageRetriever;
use Tuleap\Forum\PermissionToAccessForumException;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../forum/forum_utils.php';

$request = HTTPRequest::instance();

$params = [];

$vMsg = new Valid_UInt('msg_id');
$vMsg->required();
if ($request->valid($vMsg)) {
    $msg_id = $request->get('msg_id');

    if ($request->valid(new Valid_Pv())) {
        $pv = $request->get('pv');
    } else {
        $pv = 0;
    }

    $message_retriever = new MessageRetriever();
    try {
        $message = $message_retriever->getMessage($msg_id);
    } catch (PermissionToAccessForumException | MessageNotFoundException $e) {
        exit_error($Language->getText('global', 'error'), $e->getMessage());
    }

    $group_id   = $message->getProjectId();
    $forum_name = $message->getForumName();
    $forum_id   = $message->getForumId();
    $project    = (ProjectManager::instance())->getProject($group_id);

    forum_header(\Tuleap\Layout\HeaderConfigurationBuilder::get($message->getSubject())
        ->inProject($project, Service::FORUM)
        ->withPrinterVersion((int) $pv)
        ->build());

    echo "<P>";

    $title_arr   = [];
    $title_arr[] = 'Message: ' . $msg_id;

    echo html_build_list_table_top($title_arr);

    $purifier = Codendi_HTMLPurifier::instance();
    $poster   = UserManager::instance()->getUserByUserName($message->getUserName());
    echo "<TR><TD class=\"threadmsg\">\n";
    echo _('By') . ": " . UserHelper::instance()->getLinkOnUser($poster) . "<BR>";
    echo _('Date') . ": " . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $message->getDate()) . "<BR>";
    echo _('Subject') . ": " . $message->getSubject() . "<P>";
    echo $purifier->purify($message->getBody(), CODENDI_PURIFIER_BASIC, $group_id);
    echo "</TD></TR>";

    $crossref_fact = new CrossReferenceFactory($msg_id, ReferenceManager::REFERENCE_NATURE_FORUMMESSAGE, $group_id);
    $crossref_fact->fetchDatas();
    if ($crossref_fact->getNbReferences() > 0) {
        echo '<tr>';
        echo ' <td class="forum_reference_separator">';
        echo '  <b> ' . $Language->getText('cross_ref_fact_include', 'references') . '</b>';
        echo $crossref_fact->getHTMLDisplayCrossRefs();
        echo ' </td>';
        echo '</tr>';
    }

    echo "</TABLE>";

    if ($pv == 0) {
    /*
     Show entire thread
    */
        echo '<BR>&nbsp;<P><H3>' . _('Thread View') . '</H3>';

        //highlight the current message in the thread list
        $current_message = $msg_id;
        echo show_thread($message->getThreadId());

    /*
     Show post followup form
    */

        echo '<P>&nbsp;<P>';
        echo '<CENTER><h3>' . _('Post a followup to this message') . '</h3></CENTER>';

        show_post_form($forum_id, $message->getThreadId(), $msg_id, $message->getSubject());
    }
} else {
    exit_error($Language->getText('global', 'error'), _('Must choose a message first'));
}

forum_footer();
