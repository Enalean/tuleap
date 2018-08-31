<?php
#
# Copyright (c) Enalean, 2011-2018. All Rights Reserved.
# Copyright (c) STMicroelectronics, 2005. All Rights Reserved.

 # Originally written by Jean-Philippe Giola, 2005
 #
 # This file is a part of Tuleap.
 #
 # Tuleap is free software; you can redistribute it and/or modify
 # it under the terms of the GNU General Public License as published by
 # the Free Software Foundation; either version 2 of the License, or
 # (at your option) any later version.
 #
 # Tuleap is distributed in the hope that it will be useful,
 # but WITHOUT ANY WARRANTY; without even the implied warranty of
 # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 # GNU General Public License for more details.
 #
 # You should have received a copy of the GNU General Public License
 # along with Tuleap; if not, write to the Free Software
 # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 #
 # $Id$
 #

require_once('pre.php');
require_once('www/mail/mail_utils.php');
require_once('common/plugin/PluginManager.class.php');
require_once(dirname(__FILE__).'/../include/ForumML_Attachment.class.php');

$plugin_manager = PluginManager::instance();
$p              = $plugin_manager->getPluginByName('forumml');
if ($p && $plugin_manager->isPluginAvailable($p) && $p->isAllowed()) {
    $request = HTTPRequest::instance();

    $groupId = $request->getValidated('group_id', 'UInt', 0);

    $vList = new Valid_UInt('list');
    $vList->required();
    // Checks 'list' parameter
    if (! $request->valid($vList)) {
        exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','specify_list'));
    } else {
        $list_id = $request->get('list');
        if (!user_isloggedin() || (!mail_is_list_public($list_id) && !user_ismember($groupId))) {
            exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('include_exit','no_perm'));
        }
        if (!mail_is_list_active($list_id)) {
            exit_error($GLOBALS["Language"]->getText('global','error'),$GLOBALS["Language"]->getText('plugin_forumml','wrong_list'));
        }
    }

    // Topic
    $vTopic = new Valid_UInt('topic');
    $vTopic->required();
    if ($request->valid($vTopic)) {
        $topic = $request->get('topic');
    } else {
        $topic = 0;
    }

    $attchmentId = $request->getValidated('id', 'UInt', 0);
    if ($attchmentId) {
        $fmlAttch = new ForumML_Attachment();
        $attch = $fmlAttch->getById($attchmentId);
        if ($attch && file_exists($attch['file_path'])) {
            $response = new \Tuleap\Http\BinaryFileResponse($attch['file_path'], $attch['file_name'], $attch['type']);
            $response->send();
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS["Language"]->getText('plugin_forumml','attchment_not_found'));
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS["Language"]->getText('plugin_forumml','missing_param'));
    }
    $GLOBALS['Response']->redirect('/plugins/forumml/message.php?group_id='.$groupId.'&list='.$list_id.'&topic='.$topic);
} else {
    header('Location: /');
}
