<?php
require_once('pre.php');    
require_once('www/admin/admin_utils.php');
require_once('common/event/EventManager.class.php');

function showPendingVersions($params, $offsetVers, $limit) {
    $hp = Codendi_HTMLPurifier::instance();

    if ($params['nbVersions'] > 0) {

        echo '<H3>'.$GLOBALS['Language']->getText('admin_show_pending_documents', 'deleted_version').'</H3><P>';
        echo html_build_list_table_top ($params['tableVers']);
        $i=1;

        foreach ($params['versions'] as $row ){
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

        if ($offsetVers > 0) {
            echo  '<a href="?group_id='.$params['group_id'].'&offsetVers='.($offsetVers -$limit).'">[ '.$GLOBALS['Language']->getText('admin_show_pending_documents', 'previous').'  ]</a>';
            echo '&nbsp;';
        }
        if (($offsetVers + $limit) < $params['nbVersions']) {
            echo '&nbsp;';
            echo '<a href="?group_id='.$params['group_id'].'&offsetVers='.($offsetVers+$limit).'">[ '.$GLOBALS['Language']->getText('admin_show_pending_documents', 'next').' ]</a>';
        }
        echo '</div>';
        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
        echo ($offsetVers+$i-3).'/'.$params['nbVersions'];
        echo '</div>';

    } else {
        $GLOBALS['Response']->addFeedback('info',$GLOBALS['Language']->getText('admin_show_pending_documents', 'no_pending_versions'));
    }

}

function showPendingItems($params, $offsetItem, $limit) {
    $hp = Codendi_HTMLPurifier::instance();
    $uh = UserHelper::instance();

    if ($params['nbItems'] > 0) {
        echo '<H3>'.$GLOBALS['Language']->getText('admin_show_pending_documents', 'deleted_item').'</H3><P>';
        echo html_build_list_table_top ($params['tableItem']);
        $i=1;

        foreach ($params['items'] as $row ){
            echo '
            <TR class="'. html_get_alt_row_color($i++) .'"><TD>'. $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $params['group_id']).'</TD><TD>';
            echo $hp->purify($row['location']);
            echo '</TD>'.
                '<TD>'.$uh->getDisplayNameFromUserId($row['user']).'</TD>'.
                '<TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['date']).'</TD>'.
                '<TD align="center"><a href="" ><IMG SRC="'.util_get_image_theme("trash-x.png").'" BORDER=0 HEIGHT=16 WIDTH=16></a></TD></TR>';
        }
        echo '
        </TABLE>'; 

        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';

        if ($offsetItem > 0) {
            echo  '<a href="?group_id='.$params['group_id'].'&offsetVers='.($offsetItem -$limit).'">[ '.$GLOBALS['Language']->getText('admin_show_pending_documents', 'previous').'  ]</a>';
            echo '&nbsp;';
        }
        if (($offsetItem + $limit) < $params['nbItems']) {
            echo '&nbsp;';
            echo '<a href="?group_id='.$params['group_id'].'&offsetVers='.($offsetItem+$limit).'">[ '.$GLOBALS['Language']->getText('admin_show_pending_documents', 'next').' ]</a>';
        }
        echo '</div>';
        echo '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
        echo ($offsetItem+$i-3).'/'.$params['nbItems'];
        echo '</div>';

    } else {
       $GLOBALS['Response']->addFeedback('info',$GLOBALS['Language']->getText('admin_show_pending_documents', 'no_pending_versions'));
    }

}
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

$offsetVers = $request->getValidated('offsetVers', 'uint', 0);
if ( !$offsetVers || $offsetVers < 0 ) {
    $offsetVers = 0;
}

$offsetItem = $request->getValidated('offsetItem', 'uint', 0);
if ( !$offsetItem || $offsetItem < 0 ) {
    $offsetItem = 0;
}
$limit  = 10;

$params = array('service' => &$service,
                'group_id' => $group_id,
                'limit' => $limit, 
                'offsetVers' => $offsetVers,
                'versions' => &$versions,
                'nbVersions' =>  &$nbVersions,
                'tableVers'=>&$tableVers,
                'offsetItem' => $offsetItem,
                'items' => &$items,
                'nbItems' =>&$nbItems,
                'tableItem'=>&$tableItem);

$em->processEvent('show_pending_documents', $params);
if (isset($params['service']) && $params['service']) {
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
                <?php
                if (isset($params['versions']) && $params['versions']) {
                    showPendingVersions($params, $offsetVers, $limit);
                } else {
                    echo $GLOBALS['Language']->getText('admin_show_pending_documents', 'no_pending_versions');
                }
                ?>
            </div>
            
            <div class="contenu_onglet" id="contenu_onglet_item">
                <h1>Deleted items</h1>
                <?php 
                if (isset($params['items']) && $params['items']) {
                    showPendingItems($params, $offsetItem, $limit);
                } else {
                    echo $GLOBALS['Language']->getText('admin_show_pending_documents', 'no_pending_items');
                    
                }
                ?> 
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
} else {
    $GLOBALS['Response']->addFeedback('warning',$GLOBALS['Language']->getText('admin_show_pending_documents', 'service_not_enabled'));
}
site_admin_footer(array());
?>