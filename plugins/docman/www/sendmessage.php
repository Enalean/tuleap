<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/docmanPlugin.class.php';


$request = HTTPRequest::instance();
$func = $request->getValidated('func', new Valid_WhiteList('func', array('docman_access_request')));

if ($request->isPost() && $request->exist('Submit') &&  $request->existAndNonEmpty('func') && $func == 'docman_access_request') {
    $defaultMsg = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
    $pm = ProjectManager::instance();
    $dar = $pm->getMessageToRequesterForAccessProject($request->get('groupId'));
    if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
        $row = $dar->current();
        if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester" ) {
            $defaultMsg = $row['msg_to_requester'];
        }
    }

    $sendMail = new Docman_Error_PermissionDenied();
    $vMessage = new Valid_Text('msg_docman_access');
    $vMessage->required();
    if ($request->valid($vMessage) && (trim($request->get('msg_docman_access')) != $defaultMsg)) {
        $messageToAdmin = $request->get('msg_docman_access');
    } else {
        exit_error($Language->getText('plugin_docman', 'error'),$Language->getText('plugin_docman','invalid_msg'));
    }
    $sendMail->processMail($messageToAdmin);
    exit;
}


$HTML->header(array('title'=>$Language->getText('sendmessage', 'title',array($to_msg))));

?>