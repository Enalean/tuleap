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

require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/plugin/PluginManager.class.php');
require_once(dirname(__FILE__).'/../include/Docman_VersionFactory.class.php');
require_once(dirname(__FILE__).'/../include/Docman_ItemFactory.class.php');
require_once(dirname(__FILE__).'/../include/Docman_Controller.class.php');

$request = HTTPRequest::instance();
$pm      = PluginManager::instance();
$p       = $pm->getPluginByName('docman');
if ($p && $pm->isPluginAvailable($p)) {
    // Need to setup the controller so the notification & logging works (setup in controler constructor)
    $controler = new Docman_Controller($p, $p->getPluginPath(), $p->getThemePath(), $request);
} else {
    $GLOBALS['Response']->redirect('/');
}

$func    = $request->getValidated('func', new Valid_WhiteList('func', array('confirm_restore_item', 'confirm_restore_version')));
$groupId = $request->getValidated('group_id', 'uint', 0);
$id      = $request->getValidated('id', 'uint', 0);

$csrf_token = new CSRFSynchronizerToken('/admin/show_pending_documents.php?group_id=' . urlencode($groupId));
$csrf_token->check();

if ($request->existAndNonEmpty('func')) {

    switch ($func) {
        case 'confirm_restore_item':
            $itemFactory = new Docman_ItemFactory($groupId);
            $item = $itemFactory->getItemFromDb($id, array('ignore_deleted' => true));
            if ($itemFactory->restore($item)) {
                $url = $p->getPluginPath().'/?group_id='.$groupId.'&action=details&id='.$id.'&section=properties';
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_docman', 'item_restored', array($url)), CODENDI_PURIFIER_DISABLED);
                $GLOBALS['Response']->redirect('/admin/show_pending_documents.php?group_id='.$groupId.'&focus=item');
            } else {
                exit_error($Language->getText('plugin_docman', 'error'),$Language->getText('plugin_docman','item_not_restored'));
            }
            break;

        case 'confirm_restore_version':
            $versionFactory = new Docman_VersionFactory();
            $version = $versionFactory->getSpecificVersionById($id);
            if ($versionFactory->restore($version)) {
                $url = $p->getPluginPath().'/?group_id='.$groupId.'&action=details&id='.$version->getItemId().'&section=history';
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_docman', 'version_restored', array($url)), CODENDI_PURIFIER_DISABLED);
                $GLOBALS['Response']->redirect('/admin/show_pending_documents.php?group_id='.$groupId.'&focus=version');
            } else {
                exit_error($Language->getText('plugin_docman', 'error'),$Language->getText('plugin_docman','version_not_restored'));
            }
            break;

        default:
            break;
    }
    exit;
}
?>