<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;

require_once('pre.php');
require_once('www/admin/admin_utils.php');
require_once('common/event/EventManager.class.php');
require_once('common/wiki/lib/WikiAttachment.class.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$request = HTTPRequest::instance();
$em      = EventManager::instance();
$pm      = ProjectManager::instance();

$vFunc = new Valid_WhiteList('func', array('confirm_restore_frs_file', 'confirm_restore_wiki_attachment'));
$vFunc->required();
if ($request->valid($vFunc)) {
    $func = $request->get('func');
} else {
    $func = '';
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$project = $pm->getProject($group_id);
if (! $project->isActive()) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        $GLOBALS['Language']->getText('include_exit', 'project_status_'.$project->getStatus())
    );
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . (int) $group_id);
}

//if ($request->isPost()) {
switch ($func) {
    case 'confirm_restore_frs_file':
        frs_file_restore_process($request, $group_id);
        break;
    case 'confirm_restore_wiki_attachment':
        wiki_attachment_restore_process($request, $group_id);
        break;
}
//}

$focus = $request->get('focus');
if (!$focus) {
    $focus ='frs_file';
}

$idArray   = array();
$nomArray  = array();
$htmlArray = array();

frs_file_restore_view($group_id, $idArray, $nomArray, $htmlArray);
wiki_attachment_restore_view($group_id, $idArray, $nomArray, $htmlArray);

$params = array('group_id' => $group_id,
                'id'       => &$idArray,
                'nom'      => &$nomArray,
                'focus'    => $focus,
                'html'     => &$htmlArray
);
$em->processEvent('show_pending_documents', $params);

$purifier = Codendi_HTMLPurifier::instance();

$renderer = new AdminPageRenderer();
$renderer->header($Language->getText('admin_groupedit', 'title'), false);

?>
<div class="tlp-framed-vertically">
    <h1 class="tlp-framed-horizontally"><?= $project->getUnconvertedPublicName() ?></h1>

    <nav class="tlp-tabs">
        <a href="/admin/groupedit.php?group_id=<?= (int)$group_id ?>" class="tlp-tab">
            <?= $GLOBALS['Language']->getText('admin_project', 'information_label') ?>
        </a>
        <a href="/admin/projecthistory.php?group_id=<?= (int)$group_id ?>" class="tlp-tab">
            <?= $GLOBALS['Language']->getText('admin_project', 'history_label') ?>
        </a>
        <a href="/admin/show_pending_documents.php?group_id=<?= (int)$group_id ?>" class="tlp-tab tlp-tab-active">
            <?= $GLOBALS['Language']->getText('admin_project', 'pending_label') ?>
        </a>
    </nav>

<form action="?" method="POST" class="tlp-framed-horizontally">
<input type="hidden" name="group_id" value="<?php print (int)$group_id; ?>">
<?php
    $project = $pm->getProject($group_id,false,true);
    echo '<div class="tlp-alert-info">'.$GLOBALS['Language']->getText('admin_show_pending_documents','delay_info', array($GLOBALS['sys_file_deletion_delay'])).'</div>';
    echo '<div class="tlp-alert-info"><p>Note: there might be some delay (max 30 minutes) between the time the file is
        deleted and time it becomes restorable.<br />When a file is deleted by the user, it becomes restorable after
        SYSTEM_CHECK <a href="/admin/system_events/">system event</a> is processed.</p>';
    echo '<p>Please note that <strong>actual file restoration</strong> will be done by the
        <strong>next SYSTEM_CHECK</strong> event. This interface only schedule the restoration.</p></div>';

    foreach($params['html'] as $html) {
        echo $html;
    }
?>
</form>
</div>
<?php
$renderer->footer();

/*
 * Functions
 */

function frs_file_restore_view($group_id, &$idArray, &$nomArray, &$htmlArray) {
    $fileFactory        = new FRSFileFactory();
    $files              = $fileFactory->listPendingFiles($group_id, 0, 0);
    $toBeRestoredFiles  = $fileFactory->listToBeRestoredFiles($group_id);
    $deletedFiles       = $fileFactory->listStagingCandidates($group_id);
    $purifier           = Codendi_HTMLPurifier::instance();

    $html  = '';
    $html .= '<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">'. $GLOBALS['Language']->getText('admin_groupedit','archived_files') .'</h1>
        </div>
        <section class="tlp-pane-section">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Release name</th>
                        <th>Package name</th>
                        <th>Deleted date</th>
                        <th>Forecast purge date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';
    if ($files->rowCount() > 0) {
        foreach ($files as $file) {
            $purgeDate = strtotime('+'.$GLOBALS['sys_file_deletion_delay'].' day', $file['delete_date']);
            $html .= '<tr>';
            $html .= '<td>'.$purifier->purify($file['filename']).'</td>';
            $url   = '/file/showfiles.php?group_id='.urlencode($group_id).'#p_'.urlencode($file['package_id']).'r_'.urlencode($file['release_id']);
            $html .= '<td><a href="'.$url.'">'.$purifier->purify($file['release_name']).'</a></td>';
            $url   = '/file/showfiles.php?group_id='.urlencode($group_id).'#p_'.urlencode($file['package_id']);
            $html .= '<td><a href="'.$url.'">'.$purifier->purify(html_entity_decode($file['package_name'])).'</a></td>';
            $html .= '<td>'.html_time_ago($file['delete_date']).'</td>';
            $html .= '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $purgeDate).'</td>';
            $html .= '<td class="tlp-table-cell-actions">
                <a href="?group_id='.urlencode($group_id).'&func=confirm_restore_frs_file&id='.urlencode($file['file_id']).'"
                    class="tlp-button-small tlp-button-primary tlp-button-outline"
                    onClick="return confirm(\'Confirm restore of this file\')"
                >
                    <i class="fa fa-repeat tlp-button-icon"></i> Restore
                </td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr>
            <td class="tlp-table-cell-empty" colspan="6">
                No restorable files found
            </td>
        </tr>';
    }
    $html .= '</tbody>
        </table>
    </section>';

    if ($toBeRestoredFiles->rowCount() > 0) {
        $html .= '<section class="tlp-pane-section">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Release name</th>
                        <th>Package name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($toBeRestoredFiles as $file) {
            $html .= '<tr>';
            $html .= '<td>'.$purifier->purify($file['filename']).'</td>';
            $url   = '/file/showfiles.php?group_id='.urlencode($group_id).'#p_'.urlencode($file['package_id']).'r_'.urlencode($file['release_id']);
            $html .= '<td><a href="'.$url.'">'.$purifier->purify($file['release_name']).'</a></td>';
            $url   = '/file/showfiles.php?group_id='.urlencode($group_id).'#p_'.urlencode($file['package_id']);
            $html .= '<td><a href="'.$url.'">'.$purifier->purify($file['package_name']).'</a></td>';
            if ($file['release_status'] != FRSRelease::STATUS_DELETED
                && $file['package_status'] != FRSPackage::STATUS_DELETED) {
                $html .= '<td>File to be restored next SYSTEM_CHECK event</td>';
            } else {
                $html .= '<td>File marked to be restored in a deleted release</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>
            </table>
        </section>';
    }

    if ($deletedFiles->rowCount() > 0) {
        $html .= '<section class="tlp-pane-section">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Release name</th>
                        <th>Package name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($deletedFiles as $file) {
            $html .= '<tr>';
            $html .= '<td>'.$purifier->purify($file['filename']).'</td>';
            $url   = '/file/showfiles.php?group_id='.urlencode($group_id).'#p_'.urlencode($file['package_id']).'r_'.urlencode($file['release_id']);
            $html .= '<td><a href="'.$url.'">'.$purifier->purify($file['release_name']).'</a></td>';
            $url   = '/file/showfiles.php?group_id='.urlencode($group_id).'#p_'.urlencode($file['package_id']);
            $html .= '<td><a href="'.$url.'">'.$purifier->purify($file['package_name']).'</a></td>';
            $html .= '<td>Not yet restorable</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>
            </table>
        </section>';
    }

    $html .= '</div>
        </section>';

    $htmlArray[] = $html;
}

function frs_file_restore_process($request, $group_id) {
    $fileId = $request->getValidated('id', 'uint', 0);
    if ($fileId > 0) {
        $fileFactory = new FRSFileFactory();
        $file        = $fileFactory->getFRSFileFromDb($fileId);
        $file_name   = $file->getFileName();
        $basename = basename($file_name);
        $release_id = $file->getReleaseID();
        if (!$fileFactory->isSameFileMarkedToBeRestored($basename, $release_id, $group_id)){
            if(!$fileFactory->isFileNameExist($file_name, $group_id)){
                if ($fileFactory->markFileToBeRestored($file)) {
                    $GLOBALS['Response']->addFeedback('info', 'File marked to be restored');
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'File not restored');
                }
            }else {
                $GLOBALS['Response']->addFeedback('error', 'There is already a file with this filename having an active status');
            }
        } else {$GLOBALS['Response']->addFeedback('error', 'A file with a same name is marked to be restored');}
    } else {
        $GLOBALS['Response']->addFeedback('error', 'Bad file id');
    }
    $GLOBALS['Response']->redirect('?group_id='.$group_id);
}

function wiki_attachment_restore_view($group_id, &$idArray, &$nomArray, &$htmlArray) {
    $wikiAttachment = new WikiAttachment($group_id);
    $attachments    = $wikiAttachment->listPendingAttachments($group_id, 0, 0);
    $purifier       = Codendi_HTMLPurifier::instance();

    $tabbed_content  = '';
    $tabbed_content .= '<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">'. $GLOBALS['Language']->getText('admin_groupedit', 'archived_wiki') .'</h1>
        </div>
        <section class="tlp-pane-section">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>Attachment name</th>
                        <th>Deleted date</th>
                        <th>Forecast purge date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';

    if ($attachments->rowCount() > 0) {
        foreach ($attachments as $wiki_attachment) {
            $purgeDate = strtotime('+'.$GLOBALS['sys_file_deletion_delay'].' day', $wiki_attachment['delete_date']);
            $nonRestorableAttachments = $wikiAttachment->getDao()->getIdFromFilename($group_id, $wiki_attachment['name']);
            $tabbed_content .= '<tr>';
            $tabbed_content .= '<td>'.$purifier->purify($wiki_attachment['name']).'</td>';
            $tabbed_content .= '<td>'.html_time_ago($wiki_attachment['delete_date']).'</td>';
            $tabbed_content .= '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $purgeDate).'</td>';
            $tabbed_content .= '<td class="tlp-table-cell-actions">';
            if ($nonRestorableAttachments->rowCount()) {
                $tabbed_content .= '<button type="button"
                            class="tlp-button-small tlp-button-primary tlp-button-outline tlp-tooltip tlp-tooltip-left"
                            data-tlp-tooltip="Non-restorable attachment"
                            disabled>
                        <i class="fa fa-repeat tlp-button-icon"></i> Restore
                    </button>';
            } else {
                $tabbed_content .= '<a href="?group_id='.urlencode($group_id).'&func=confirm_restore_wiki_attachment&id='.urlencode($wiki_attachment['id']).'"
                        class="tlp-button-small tlp-button-primary tlp-button-outline"
                        onClick="return confirm(\'Confirm restore of this attachment\')"
                    >
                        <i class="fa fa-repeat tlp-button-icon"></i> Restore
                    </a>';
            }
            $tabbed_content .= '</td>';
            $tabbed_content .= '</tr>';
        }
    } else {
        $tabbed_content .= '<tr>
            <td class="tlp-table-cell-empty" colspan="6">
                No restorable attachments found
            </td>
        </tr>';
    }
    $tabbed_content .= '</table>
                </section>
            </div>
        </section>';

    $htmlArray[] = $tabbed_content;
}

function wiki_attachment_restore_process($request, $group_id) {
    $attachmentId = $request->getValidated('id', 'uint', 0);
    if ($attachmentId > 0) {
        $wikiAttachment = new WikiAttachment($group_id);
        $wikiAttachment->initWithId($attachmentId);
            if($wikiAttachment->restoreDeletedAttachment($attachmentId)) {
                $GLOBALS['Response']->addFeedback('info', 'Wiki attachment restored');
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Wiki attachment not restored');
            }
    } else {
        $GLOBALS['Response']->addFeedback('error', 'Bad attachment id');
    }
    $GLOBALS['Response']->redirect('?group_id='.$group_id.'&focus=wiki_attachment');
}
