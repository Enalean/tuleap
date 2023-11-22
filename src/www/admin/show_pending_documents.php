<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\PendingElements\PendingDocumentsRetriever;
use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\Date\DateHelper;
use Tuleap\Date\RelativeDatesAssetsRetriever;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/admin_utils.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$em = EventManager::instance();
$pm = ProjectManager::instance();

$vFunc = new Valid_WhiteList('func', ['confirm_restore_frs_file', 'confirm_restore_wiki_attachment']);
$vFunc->required();
if ($request->valid($vFunc)) {
    $func = $request->get('func');
} else {
    $func = '';
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$project = $pm->getProject($group_id);
if (! $project->isActive()) {
    switch ($project->getStatus()) {
        case Project::STATUS_DELETED:
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('This project is deleted')
            );
            break;
        case Project::STATUS_PENDING:
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('include_exit', 'project_status_P')
            );
            break;
        case Project::STATUS_SUSPENDED:
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('This project is suspended')
            );
            break;
        case Project::STATUS_SYSTEM:
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('include_exit', 'project_status_s')
            );
            break;
    }
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . (int) $group_id);
}

$csrf_token = new CSRFSynchronizerToken('/admin/show_pending_documents.php?group_id=' . urlencode($group_id));

switch ($func) {
    case 'confirm_restore_frs_file':
        $csrf_token->check();
        frs_file_restore_process($request, $group_id);
        break;
    case 'confirm_restore_wiki_attachment':
        $csrf_token->check();
        wiki_attachment_restore_process($request, $group_id);
        break;
}

$core_assets      = new \Tuleap\Layout\IncludeCoreAssets();
$detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest(HTTPRequest::instance());
$GLOBALS['Response']->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());

$focus = $request->get('focus');
if (! $focus) {
    $focus = 'frs_file';
}

$idArray   = [];
$nomArray  = [];
$htmlArray = [];

$user = $request->getCurrentUser();

$event = new PendingDocumentsRetriever($project, $user, $csrf_token);

frs_file_restore_view($group_id, $csrf_token, $event, $user);
wiki_attachment_restore_view($group_id, $csrf_token, $event, $user);

$em->processEvent($event);

$purifier = Codendi_HTMLPurifier::instance();

$renderer = new AdminPageRenderer();
$renderer->header(_('Editing Project'), false);

?>
<div class="tlp-framed-vertically">
    <h1 class="tlp-framed-horizontally"><?php echo $purifier->purify($project->getPublicName()) ?></h1>

    <nav class="tlp-tabs">
        <a href="/admin/groupedit.php?group_id=<?php echo (int) $group_id ?>" class="tlp-tab">
            <?php echo $GLOBALS['Language']->getText('admin_project', 'information_label') ?>
        </a>
        <a href="/admin/userlist.php?group_id=<?php echo (int) $group_id ?>" class="tlp-tab">
            <?php echo $GLOBALS['Language']->getText('admin_project', 'members_label') ?>
        </a>
        <a href="/admin/projecthistory.php?group_id=<?php echo (int) $group_id ?>" class="tlp-tab">
            <?php echo $GLOBALS['Language']->getText('admin_project', 'history_label') ?>
        </a>
        <a href="/admin/show_pending_documents.php?group_id=<?php echo (int) $group_id ?>" class="tlp-tab tlp-tab-active">
            <?php echo $GLOBALS['Language']->getText('admin_project', 'pending_label') ?>
        </a>
    </nav>
    <main role="main" class="tlp-framed">
    <?php
        $project = $pm->getProject($group_id);
        echo '<div class="tlp-alert-info">' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'delay_info', [ForgeConfig::get('sys_file_deletion_delay')]) . '</div>';
        echo '<div class="tlp-alert-info"><p>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'note_intro') . '<br />';
        echo $GLOBALS['Language']->getText('admin_show_pending_documents', 'note_intro_system') . ' <a href="/admin/system_events/">system event</a> ';
        echo $GLOBALS['Language']->getText('admin_show_pending_documents', 'note_intro_system_end') . '</p>';
        echo '<p>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'note_intro_restaure') . '</p></div>';

    foreach ($event->getHtml() as $html) {
        echo $html;
    }
    ?>
    </div>
</main>
<?php
$renderer->footer();

/*
 * Functions
 */

function frs_file_restore_view($group_id, CSRFSynchronizerToken $csrf_token, PendingDocumentsRetriever $event, PFUser $user)
{
    $fileFactory       = new FRSFileFactory();
    $files             = $fileFactory->listPendingFiles($group_id, 0, 0);
    $toBeRestoredFiles = $fileFactory->listToBeRestoredFiles($group_id);
    $deletedFiles      = $fileFactory->listStagingCandidates($group_id);
    $purifier          = Codendi_HTMLPurifier::instance();

    $html  = '';
    $html .= '<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">' . _('Deleted files') . '</h1>
        </div>
        <section class="tlp-pane-section">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_filename') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_release') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_package') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_deleted') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_forecast') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';
    if ($files->rowCount() > 0) {
        foreach ($files as $file) {
            $purgeDate = strtotime('+' . ForgeConfig::get('sys_file_deletion_delay') . ' day', $file['delete_date']);
            $html     .= '<tr>';
            $html     .= '<td>' . $purifier->purify($file['filename']) . '</td>';
            $url       = '/file/showfiles.php?group_id=' . urlencode($group_id) . '#p_' . urlencode(
                $file['package_id']
            ) . 'r_' . urlencode($file['release_id']);
            $html     .= '<td><a href="' . $url . '">' . $purifier->purify($file['release_name']) . '</a></td>';
            $url       = '/file/showfiles.php?group_id=' . urlencode($group_id) . '#p_' . urlencode($file['package_id']);
            $html     .= '<td><a href="' . $url . '">' . $purifier->purify(
                html_entity_decode($file['package_name'])
            ) . '</a></td>';
            $html     .= '<td>' . DateHelper::relativeDateInlineContext((int) $file['delete_date'], $user) . '</td>';
            $html     .= '<td>' . DateHelper::relativeDateInlineContext((int) $purgeDate, $user) . '</td>';
            $html     .= '<td class="tlp-table-cell-actions">';
            $html     .= '<form method="post" onsubmit="return confirm(\'' . $GLOBALS['Language']->getText(
                'admin_show_pending_documents',
                'frs_confirm_message'
            ) . '\')">';
            $html     .= $csrf_token->fetchHTMLInput();
            $html     .= '<input type="hidden" name="func" value="confirm_restore_frs_file">';
            $html     .= '<input type="hidden" name="id" value="' . $purifier->purify($file['file_id']) . '">';
            $html     .= '<button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                        <i class="fas fa-redo tlp-button-icon"></i> ' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_restore') . '</button>';
            $html     .= '</form></td>';
            $html     .= '</tr>';
        }
    } else {
        $html .= '<tr>
            <td class="tlp-table-cell-empty" colspan="6">
                ' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_no_restore') . '
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
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_filename') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_release') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_package') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($toBeRestoredFiles as $file) {
            $html .= '<tr>';
            $html .= '<td>' . $purifier->purify($file['filename']) . '</td>';
            $url   = '/file/showfiles.php?group_id=' . urlencode($group_id) . '#p_' . urlencode(
                $file['package_id']
            ) . 'r_' . urlencode($file['release_id']);
            $html .= '<td><a href="' . $url . '">' . $purifier->purify($file['release_name']) . '</a></td>';
            $url   = '/file/showfiles.php?group_id=' . urlencode($group_id) . '#p_' . urlencode($file['package_id']);
            $html .= '<td><a href="' . $url . '">' . $purifier->purify($file['package_name']) . '</a></td>';
            if (
                $file['release_status'] != FRSRelease::STATUS_DELETED
                && $file['package_status'] != FRSPackage::STATUS_DELETED
            ) {
                $html .= '<td>' . $GLOBALS['Language']->getText(
                    'admin_show_pending_documents',
                    'frs_restore_info'
                ) . '</td>';
            } else {
                $html .= '<td>' . $GLOBALS['Language']->getText(
                    'admin_show_pending_documents',
                    'frs_restore_file_info'
                ) . '</td>';
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
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_filename') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_release') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_package') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($deletedFiles as $file) {
            $html .= '<tr>';
            $html .= '<td>' . $purifier->purify($file['filename']) . '</td>';
            $url   = '/file/showfiles.php?group_id=' . urlencode($group_id) . '#p_' . urlencode(
                $file['package_id']
            ) . 'r_' . urlencode($file['release_id']);
            $html .= '<td><a href="' . $url . '">' . $purifier->purify($file['release_name']) . '</a></td>';
            $url   = '/file/showfiles.php?group_id=' . urlencode($group_id) . '#p_' . urlencode($file['package_id']);
            $html .= '<td><a href="' . $url . '">' . $purifier->purify($file['package_name']) . '</a></td>';
            $html .= '<td>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_not_yet') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>
            </table>
        </section>';
    }

    $html .= '</div>
        </section>';

    $event->addPurifiedHTML($html);
}

function frs_file_restore_process($request, $group_id)
{
    $fileId = $request->getValidated('id', 'uint', 0);
    if ($fileId > 0) {
        $fileFactory = new FRSFileFactory();
        $file        = $fileFactory->getFRSFileFromDb($fileId);
        $file_name   = $file->getFileName();
        $basename    = basename($file_name);
        $release_id  = $file->getReleaseID();
        if (! $fileFactory->isSameFileMarkedToBeRestored($basename, $release_id)) {
            if (! $fileFactory->isFileNameExist($file_name, $group_id)) {
                if ($fileFactory->markFileToBeRestored($file)) {
                    $GLOBALS['Response']->addFeedback(
                        'info',
                        $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_restored')
                    );
                } else {
                    $GLOBALS['Response']->addFeedback(
                        'error',
                        $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_not_restored')
                    );
                }
            } else {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_active')
                );
            }
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_same')
            );
        }
    } else {
        $GLOBALS['Response']->addFeedback(
            'error',
            $GLOBALS['Language']->getText('admin_show_pending_documents', 'frs_bad')
        );
    }
    $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id);
}

function wiki_attachment_restore_view($group_id, CSRFSynchronizerToken $csrf_token, PendingDocumentsRetriever $event, PFUser $user)
{
    $wikiAttachment = new WikiAttachment($group_id);
    $attachments    = $wikiAttachment->listPendingAttachments($group_id, 0, 0);
    $purifier       = Codendi_HTMLPurifier::instance();

    $tabbed_content  = '';
    $tabbed_content .= '<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">' . _('Deleted wiki attachments') . '</h1>
        </div>
        <section class="tlp-pane-section">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_name') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_date') . '</th>
                        <th>' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_purge') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';

    if ($attachments->rowCount() > 0) {
        foreach ($attachments as $wiki_attachment) {
            $purgeDate                = strtotime('+' . ForgeConfig::get('sys_file_deletion_delay') . ' day', $wiki_attachment['delete_date']);
            $nonRestorableAttachments = $wikiAttachment->getDao()->getIdFromFilename($group_id, $wiki_attachment['name']);
            $tabbed_content          .= '<tr>';
            $tabbed_content          .= '<td>' . $purifier->purify($wiki_attachment['name']) . '</td>';
            $tabbed_content          .= '<td>' . DateHelper::relativeDateInlineContext((int) $wiki_attachment['delete_date'], $user) . '</td>';
            $tabbed_content          .= '<td>' . DateHelper::relativeDateInlineContext((int) $purgeDate, $user)  . '</td>';
            $tabbed_content          .= '<td class="tlp-table-cell-actions">';
            if ($nonRestorableAttachments->rowCount()) {
                $tabbed_content .= '<button type="button"
                            class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline tlp-tooltip tlp-tooltip-left"
                            data-tlp-tooltip="Non-restorable attachment"
                            disabled>
                        <i class="fas fa-redo tlp-button-icon"></i> ' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_restore') . '
                    </button>';
            } else {
                $tabbed_content .= '<form method="POST" onsubmit="return confirm(\'Confirm restore of this attachment\');">
                        <input type="hidden" name="func" value="confirm_restore_wiki_attachment">
                        <input type="hidden" name="id" value="' . $purifier->purify($wiki_attachment['id']) . '">
                        <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                        <i class="fas fa-redo tlp-button-icon"></i> ' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_restore') . '</button>';
                $tabbed_content .= $csrf_token->fetchHTMLInput();
                $tabbed_content .= '</form>';
            }
            $tabbed_content .= '</td>';
            $tabbed_content .= '</tr>';
        }
    } else {
        $tabbed_content .= '<tr>
            <td class="tlp-table-cell-empty" colspan="6">
                ' . $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_no_restore') . '
            </td>
        </tr>';
    }
    $tabbed_content .= '</table>
                </section>
            </div>
        </section>';

    $event->addPurifiedHTML($tabbed_content);
}

function wiki_attachment_restore_process($request, $group_id)
{
    $attachmentId = $request->getValidated('id', 'uint', 0);
    if ($attachmentId > 0) {
        $wikiAttachment = new WikiAttachment($group_id);
        $wikiAttachment->initWithId($attachmentId);
        if ($wikiAttachment->restoreDeletedAttachment($attachmentId)) {
            $GLOBALS['Response']->addFeedback(
                'info',
                $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_restore_attachment')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_restore_error')
            );
        }
    } else {
        $GLOBALS['Response']->addFeedback(
            'error',
            $GLOBALS['Language']->getText('admin_show_pending_documents', 'wiki_bad_id')
        );
    }
    $GLOBALS['Response']->redirect('?group_id=' . (int) $group_id . '&focus=wiki_attachment');
}
