<?php
require_once('pre.php');    
require_once('www/admin/admin_utils.php');
require_once('common/event/EventManager.class.php');

function showPendingDocuments($params, $offsetPending, $limitPending) {
    global $Language;
    $hp = Codendi_HTMLPurifier::instance();

    if ($params['numrows'] > 0) {

        echo '
        <H3> Deleted versions </H3>
        <P>';
        echo html_build_list_table_top ($params['titles']);
        $i=1;

        foreach ($params['pendings'] as $row ){
            echo '
            <TR class="'. html_get_alt_row_color($i++) .'"><TD>'. $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $params['group_id']).'</TD><TD>';
            echo $hp->purify($row['label']);
            echo '</TD>'.
                '<TD>'.$row['number'].'</TD>'.
                '<TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['date']).'</TD>'.
                '<TD align="center"><a href="" ><IMG SRC="'.util_get_image_theme("trash-x.png").'" BORDER=0 HEIGHT=16 WIDTH=16></a></TD></TR>';
        }
        echo '
        </TABLE>'; 

        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';

        if ($offsetPending > 0) {
            echo  '<a href="?group_id='.$params['group_id'].'&offsetPending='.($offsetPending -$limitPending).'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
            echo '&nbsp;';
        }
        if (($offsetPending + $limitPending) < $params['numrows']) {
            echo '&nbsp;';
            echo '<a href="?group_id='.$params['group_id'].'&offsetPending='.($offsetPending+$limitPending).'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
        }
        echo '</div>';
        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
        echo ($offsetPending+$i-3).'/'.$params['numrows'];
        echo '</div>';

    } else {
        echo '
        <H3>No pending documents</H3>';
    }

}
site_admin_header(array('title'=>$Language->getText('admin_groupedit','title')));
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

$offsetPending = $request->getValidated('offsetPending', 'uint', 0);
if ( !$offsetPending || $offsetPending < 0 ) {
    $offsetPending = 0;
}
$limitPending  = 10;

$params = array('group_id' => $group_id, 
                'offset' => $offsetPending,
                'limit' => $limitPending,
                'pendings' => &$pendings,
                'numrows' =>  &$numrows,
                'titles' => &$titles);
$em->processEvent('show_pending_versions', $params);
if (isset($params['pendings']) && $params['pendings']) {
?>

<FORM action="?" method="POST">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<?php echo "<h3> Pending deleted document </h3>" ; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript">
        //<!--
                function change_onglet(name)
                {
                        document.getElementById('onglet_'+anc_onglet).className = 'onglet_0 onglet';
                        document.getElementById('onglet_'+name).className = 'onglet_1 onglet';
                        document.getElementById('contenu_onglet_'+anc_onglet).style.display = 'none';
                        document.getElementById('contenu_onglet_'+name).style.display = 'block';
                        anc_onglet = name;
                }
        //-->
        </script>
    <style type="text/css">
        .onglet
        {
                display:inline-block;
                margin-left:3px;
                margin-right:3px;
                padding:3px;
                border:1px solid black;
                cursor:pointer;
        }
        .onglet_0
        {
                background:#bbbbbb;
                border-bottom:1px solid black;
        }
        .onglet_1
        {
                background:#dddddd;
                border-bottom:0px solid black;
                padding-bottom:4px;
        }
        .contenu_onglet
        {
                background-color:#dddddd;
                border:1px solid black;
                margin-top:-1px;
                padding:5px;
                display:none;
        }
        ul
        {
                margin-top:0px;
                margin-bottom:0px;
                margin-left:-10px
        }
        h1
        {
                margin:0px;
                padding:0px;
        }
        </style>
</head>
<body>

        <div class="systeme_onglets">
        <div class="onglets">
            <span class="onglet_0 onglet" id="onglet_version" onclick="javascript:change_onglet('version');">Deleted versions</span>
            <span class="onglet_0 onglet" id="onglet_item" onclick="javascript:change_onglet('item');">Deleted items</span>
        
        </div>
        <div class="contenu_onglets">
            <div class="contenu_onglet" id="contenu_onglet_version">
                <h1>Deleted Versions</h1>
                <?php showPendingDocuments($params, $offsetPending, $limitPending);?> 
            </div>
            <div class="contenu_onglet" id="contenu_onglet_item">
                <h1>Deleted items</h1>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        //<!--
                var anc_onglet = 'version';
                change_onglet(anc_onglet);
        //-->
        </script>
</body>
</html>


</FORM>
<?php 
}
site_admin_footer(array());
?>

