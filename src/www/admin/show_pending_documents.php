<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('pre.php');
require_once('www/admin/admin_utils.php');
require_once('common/event/EventManager.class.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$request = HTTPRequest::instance();
$em      = EventManager::instance();
$pm      = ProjectManager::instance();

$vFunc = new Valid_WhiteList('func', array('confirm_restore_frs_file'));
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

//if ($request->isPost()) {
switch ($func) {
    case 'confirm_restore_frs_file':
        frs_file_restore_process($request, $group_id);
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

frs_file_restore_view($group_id, &$idArray, &$nomArray, &$htmlArray);

$params = array('group_id' => $group_id,
                'id'       => &$idArray,
                'nom'      => &$nomArray,
                'focus'    => $focus,
                'html'     => &$htmlArray
);
$em->processEvent('show_pending_documents', $params);

site_admin_header(array('title'=>$GLOBALS['Language']->getText('admin_groupedit','title')));
?>
<FORM action="?" method="POST">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<?php 
$project = $pm->getProject($group_id,false,true);
echo '<h3>'.$GLOBALS['Language']->getText('admin_show_pending_documents','pending_doc', array ($group_id, $project->getUnixName())).'</h3>'; ?>
        <div class="systeme_onglets">
            <div class="onglets">
            <?php
            if (isset($params['id']) && $params['id']) {
                $i=0;
            
                foreach($params['id'] as $id){
                    $nom = $params['nom'][$i++];
                    echo '<span class="onglet_0 onglet" id="onglet_'.$id.'">'.$nom.'</span>';
                }
            }
            ?>
            </div>
            <div class="contenu_onglets">
            <?php 
            if (isset($params['html']) && $params['html']) {
                foreach($params['html'] as $html) {
                    echo $html;
                }
            }
            ?>
            </div>
         </div>
</FORM>
<?php

$GLOBALS['HTML']->includeFooterJavascriptSnippet('
                function change_onglet(name)
                {
                        $(\'onglet_\'+anc_onglet).className = \'onglet_0 onglet\';
                        $(\'onglet_\'+name).className = \'onglet_1 onglet\';
                        $(\'contenu_onglet_\'+anc_onglet).style.display = \'none\';
                        $(\'contenu_onglet_\'+name).style.display = \'block\';
                        anc_onglet = name;
                }
                $$(\'.onglet\').each(function (e) {
                    e.observe(\'click\', function () {
                        var id = e.id.sub(\'onglet_\', \'\');
                        change_onglet(id);
                        e.stop();
                    });
                });
                var anc_onglet = \''.$focus.'\';
                change_onglet(anc_onglet);
');

site_admin_footer(array());

/*
 * Functions
 */

function frs_file_restore_view($group_id, $idArray, $nomArray, $htmlArray) {
    $fileFactory = new FRSFileFactory();
    $html  = '';
    $html .= '<div class="contenu_onglet" id="contenu_onglet_frs_file">';

    $titles = array ('Filename', 'Release name', 'Package name', 'Delete date', 'Restore');
    $html  .= html_build_list_table_top ($titles);
    $i      = 1;
    foreach ($fileFactory->listPendingFiles($group_id, 0, 0) as $file) {
        $html .= '<tr class="'. html_get_alt_row_color($i++) .'">';
        $html .= '<td>'.$file['filename'].'</td>';
        $html .= '<td>'.$file['release_name'].'</td>';
        $html .= '<td>'.$file['package_name'].'</td>';
        $html .= '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $file['delete_date']).'</td>';
        $html .= '<td align="center"><a href="?group_id='.$group_id.'&func=confirm_restore_frs_file&id='.$file['file_id'].'"><img src="'.util_get_image_theme("trash-x.png").'" onClick="return confirm(\'Confirm restore of this file\')" border="0" height="16" width="16"></a></td></tr>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $html .='</div>';

    $idArray[]   = 'frs_file';
    $nomArray[]  = 'File files';
    $htmlArray[] = $html;
}

function frs_file_restore_process($request, $group_id) {
    $fileId = $request->getValidated('id', 'uint', 0);
    if ($fileId > 0) {
        $fileFactory = new FRSFileFactory();
        $file        = $fileFactory->getFRSFileFromDb($fileId);
        if ($fileFactory->restoreFile($file)) {
            $GLOBALS['Response']->addFeedback('info', 'File restored');
        } else {
            $GLOBALS['Response']->addFeedback('error', 'File not restored');
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', 'Bad file id');
    }
    $GLOBALS['Response']->redirect('?group_id='.$group_id);
}

?>