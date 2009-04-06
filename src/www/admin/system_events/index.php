<?php
/*
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('pre.php');
require_once('common/dao/SystemEventsFollowersDao.class.php');

session_require(array('group'=>'1', 'admin_flags'=>'A'));

$sefdao = new SystemEventsFollowersDao(CodendiDataAccess::instance());
$default_new_followers_email = 'Type logins, emails or mailing lists. Multiple values separated by coma.';
if ($new_followers = $request->get('new_followers')) {
    if (isset($new_followers['emails']) && $new_followers['emails'] && $new_followers['emails'] != $default_new_followers_email) {
        if (count($new_followers['types'])) {
            $sefdao->create($new_followers['emails'], implode(',', $new_followers['types']));
            $GLOBALS['Response']->redirect('/admin/system_events/');
        }
    }
}
if ($request->get('delete')) {
    $sefdao->delete($request->get('delete'));
    $GLOBALS['Response']->redirect('/admin/system_events/');
}
if ($request->get('cancel')) {
    $GLOBALS['Response']->redirect('/admin/system_events/');
}
if ($request->get('save') && ($followers = $request->get('followers'))) {
    list($id, $info) = each($followers);
    $sefdao->save($id, $info['emails'], implode(',', $info['types']));
    $GLOBALS['Response']->redirect('/admin/system_events/');
}

$hp = Codendi_HTMLPurifier::instance();

$title = $Language->getText('admin_system_events', 'title');
$HTML->header(array('title' => $title));
echo '<h2>'.  $hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML)  .'</h2>';

$se = SystemEventManager::instance();
$offset = $request->get('offset') ? (int)$request->get('offset') : 0;
$limit  = 20;
echo $se->fetchLastEventsStatus($offset, $limit, true);

echo '<h3>'. $Language->getText('admin_system_events', 'notifications') .'</h3>';
echo '<form action="" method="POST">';
echo $GLOBALS['Language']->getText('admin_system_events', 'send_email');
$dar = $sefdao->searchAll();
if (!$dar->rowCount()) {
    echo '<em>'. $GLOBALS['Language']->getText('admin_system_events', 'nobody') .'</em>';
}
echo '<table>';
echo '<thead>';
echo '<tr><th class="boxtitle">'. 'emails' .'</th><th class="boxtitle">'. 'listen' .'</th></tr>';
echo '</thead>';
echo '<tbody>';
foreach($dar as $row) {
    echo '<tr valign="top"><td>';
    if ($request->get('edit') == $row['id']) {
        echo '<textarea name="followers['. $row['id'] .'][emails]" rows="4" cols="40">';
        echo  $hp->purify($row['emails'], CODENDI_PURIFIER_CONVERT_HTML) ;
        echo '</textarea>';
    } else {
        echo  $hp->purify($row['emails'], CODENDI_PURIFIER_CONVERT_HTML) ;
    }
    echo '</td><td>';
    $types = explode(',', $row['types']);
    if ($request->get('edit') == $row['id']) {
        echo '<select name="followers['. $row['id'] .'][types][]" size="5" multiple="multiple">';
        echo '<option value="'. SystemEvent::STATUS_NEW .'"     '. (in_array(SystemEvent::STATUS_NEW    , $types) ? 'selected="true"' : '') .'">'. SystemEvent::STATUS_NEW     .'</option>';
        echo '<option value="'. SystemEvent::STATUS_RUNNING .'" '. (in_array(SystemEvent::STATUS_RUNNING, $types) ? 'selected="true"' : '') .'">'. SystemEvent::STATUS_RUNNING .'</option>';
        echo '<option value="'. SystemEvent::STATUS_DONE .'"    '. (in_array(SystemEvent::STATUS_DONE   , $types) ? 'selected="true"' : '') .'">'. SystemEvent::STATUS_DONE    .'</option>';
        echo '<option value="'. SystemEvent::STATUS_WARNING .'" '. (in_array(SystemEvent::STATUS_WARNING, $types) ? 'selected="true"' : '') .'">'. SystemEvent::STATUS_WARNING .'</option>';
        echo '<option value="'. SystemEvent::STATUS_ERROR .'"   '. (in_array(SystemEvent::STATUS_ERROR  , $types) ? 'selected="true"' : '') .'">'. SystemEvent::STATUS_ERROR   .'</option>';
        echo '</select>';
    } else {
        echo $row['types'];
    }
    echo '</td><td>';
    if ($request->get('edit') == $row['id']) {
        echo '<input type="submit" name="save" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />';
    } else {
        echo '<a href="?edit='. $row['id'] .'">'. $GLOBALS['HTML']->getImage('ic/edit.png') .'</a>';
    }
    echo '<a onclick="return confirm(\''.  $hp->purify('Are you sure that you want to delete?', CODENDI_PURIFIER_JS_QUOTE)  .'\');" href="?delete='. $row['id'] .'">'. $GLOBALS['HTML']->getImage('ic/cross.png') .'</a>';
    echo '</td></tr>';
}
if (!$request->get('edit')) {
    echo '<tr valign="top"><td><textarea name="new_followers[emails]" id="new_followers_email" rows="4" cols="40">';
    echo  $hp->purify($default_new_followers_email, CODENDI_PURIFIER_CONVERT_HTML) ;
    echo '</textarea>';
    echo '</td><td>';
    echo '<select name="new_followers[types][]" size="5" multiple="multiple">';
    echo '<option value="'. SystemEvent::STATUS_NEW .'"     >'. SystemEvent::STATUS_NEW     .'</option>';
    echo '<option value="'. SystemEvent::STATUS_RUNNING .'" >'. SystemEvent::STATUS_RUNNING .'</option>';
    echo '<option value="'. SystemEvent::STATUS_DONE .'"    >'. SystemEvent::STATUS_DONE    .'</option>';
    echo '<option value="'. SystemEvent::STATUS_WARNING .'" selected="true">'. SystemEvent::STATUS_WARNING .'</option>';
    echo '<option value="'. SystemEvent::STATUS_ERROR .'"   selected="true">'. SystemEvent::STATUS_ERROR   .'</option>';
    echo '</select>';
    echo '</td><td>';
    echo '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
    echo '</td></tr>';
}
echo '</tbody>';
echo '</table>';
echo '</form>';
echo '<script type="text/javascript">';
echo "
document.observe('dom:loaded', function() {
    $('new_followers_email').defaultValueActsAsHint();
});
</script>";
$HTML->footer(array());

?>
