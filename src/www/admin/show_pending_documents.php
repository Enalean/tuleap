<?php
require_once('pre.php');
require_once('www/admin/admin_utils.php');
require_once('common/event/EventManager.class.php');

site_admin_header(array('title'=>$GLOBALS['Language']->getText('admin_groupedit','title')));
session_require(array('group'=>'1','admin_flags'=>'A'));
$request = HTTPRequest::instance();
$em = EventManager::instance();

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$idArray   = array();
$nomArray  = array();
$htmlArray = array();

$html  = '';
$html .= '<div class="contenu_onglet" id="contenu_onglet_frs_file">';
$fileFactory = new FRSFileFactory();

$titles = array ('Filename', 'Release name', 'Package name', 'Delete date', 'Restore');
$html  .= html_build_list_table_top ($titles);
$i      = 1;
foreach ($fileFactory->listPendingFiles($group_id, 0, 0) as $file) {
    $html .= '<tr class="'. html_get_alt_row_color($i++) .'">';
    $html .= '<td>'.$file['filename'].'</td>';
    $html .= '<td>'.$file['release_name'].'</td>';
    $html .= '<td>'.$file['package_name'].'</td>';
    $html .= '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $file['delete_date']).'</td>';
    $html .= '<td>todo</td>';
    $html .= '</tr>';
}
$html .= '</table>';
$html .='</div>';

$idArray[]   = 'frs_file';
$nomArray[]  = 'File releases';
$htmlArray[] = $html;

$params = array('group_id' => $group_id,
                'id' =>&$idArray,
               'nom'=>&$nomArray,
               'focus' =>&$focus,
               'html' => &$htmlArray
);
$em->processEvent('show_pending_documents', $params);
$params['focus'] = 'frs_file';
?>
<FORM action="?" method="POST">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<?php echo '<h3>'.$GLOBALS['Language']->getText('admin_show_pending_documents','pending_doc').'</h3>'; ?>
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
                var anc_onglet = \''.$params['focus'].'\';
                change_onglet(anc_onglet);
');

site_admin_footer(array());
?>