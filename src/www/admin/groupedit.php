<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('vars.php');
require_once('www/admin/admin_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/include/TemplateSingleton.class.php');
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

        while ($row = db_fetch_array($params['pendings'])) {
            echo '
            <TR class="'. html_get_alt_row_color($i++) .'"><TD>'. $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $params['group_id']).'</TD><TD>';
            echo $hp->purify($row['label']);
            echo '</TD>'.
                '<TD>'.$row['number'].'</TD>'.
                '<TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),$row['date']).'</TD>'.
                '<TD><A href= "<IMG SRC="'.util_get_image_theme("restore.png").'" BORDER=0 HEIGHT=12 WIDTH=10> </A></TD></TR>';
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
session_require(array('group'=>'1','admin_flags'=>'A'));
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id,false,true);
$request = HTTPRequest::instance();
$currentproject= new project($group_id);

$em = EventManager::instance();

$Rename=$request->get('Rename');
if ($Rename) {
    $new_name =$request->get('new_name');
    if (isset($new_name) && $group_id) {
        if (SystemEventManager::instance()->canRenameProject($group)) {
            $rule = new Rule_ProjectName();
            if (!$rule->isValid($new_name)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_groupedit','invalid_short_name'));
                $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            } else {
                $em->processEvent(Event::PROJECT_RENAME, array('group_id' => $group_id,
                                                           'new_name' => $new_name));
                //update group history
                group_add_history('rename_request', $group->getUnixName(false).' :: '.$new_name, $group_id);
                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_groupedit','rename_project_msg', array($group->getUnixName(false), $new_name )));
                $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_groupedit','rename_project_warn'));
                
            }
        }else {
            $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_groupedit', 'rename_project_already_queued'), CODENDI_PURIFIER_DISABLED);
        }
    }
}        
// group public choice
$Update=$request->get('Update');
if ($Update) {
    $res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

	//audit trail
        if ($group->getStatus() != $form_status)
		{ group_add_history ('status',$group->getStatus(),$group_id);  }
	if ($group->isPublic() != $form_public) { 
        group_add_history('is_public',$group->isPublic(),$group_id);
        $em->processEvent('project_is_private', array(
            'group_id'           => $group_id, 
            'project_is_private' => $form_public ? 0 : 1
        ));
    }
	if ($group->getType() != $group_type)
		{ group_add_history ('group_type',$group->getType(),$group_id);  }
	if ($group->getHTTPDomain()!= $form_domain)
		{ group_add_history ('http_domain',$group->getHTTPDomain(),$group_id);  }
	if ($group->getUnixBox() != $form_box)
		{ group_add_history ('unix_box',$group->getUnixBox(),$group_id);  }
	db_query("UPDATE groups SET is_public=$form_public,status='$form_status',"
		. "license='$form_license',type='$group_type',"
		. "unix_box='$form_box',http_domain='$form_domain', "
		. "type='$group_type' WHERE group_id=$group_id");

	$feedback .= $Language->getText('admin_groupedit','feedback_info');

	$group = $pm->getProject($group_id,false,true);
	
	// ZD: Raise an event for group update 
        if(isset($form_status) && $form_status && ($form_status=="H" || $form_status=="P")){
	        $em->processEvent('project_is_suspended_or_pending', array(
	            'group_id'       => $group_id
	        ));
        }else if(isset($form_status) && $form_status && $form_status=="A" ){
        	$em->processEvent('project_is_active', array(
	            'group_id'       => $group_id
	        ));
        }else if(isset($form_status) && $form_status && $form_status=="D"){
        	$em->processEvent('project_is_deleted', array('group_id' => $group_id ));
        }

}

// get current information
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

if (db_numrows($res_grp) < 1) {
	exit_error("ERROR",$Language->getText('admin_groupedit','error_group'));
}

$row_grp = db_fetch_array($res_grp);

site_admin_header(array('title'=>$Language->getText('admin_groupedit','title')));

echo '<H2>'.$row_grp['group_name'].'</H2>' ;?>

<p>
<A href="/project/admin/?group_id=<?php print $group_id; ?>"><B><BIG>[<?php echo $Language->getText('admin_groupedit','proj_admin'); ?>]</BIG></B></A><BR/>
<A href="userlist.php?group_id=<?php print $group_id; ?>"><B><BIG>[<?php echo $Language->getText('admin_groupedit','proj_member'); ?>]</BIG></B></A>
</p>

<p>
<FORM action="?" method="POST">
<B><?php echo $Language->getText('admin_groupedit','group_type'); ?>:</B>
<?php

$template =& TemplateSingleton::instance();
echo $template->showTypeBox('group_type',$group->getType());

?>

<B><?php echo $Language->getText('global','status'); ?></B>
<SELECT name="form_status">
<OPTION <?php if ($row_grp['status'] == "I") print "selected "; ?> value="I">
<?php echo $Language->getText('admin_groupedit','incomplete'); ?></OPTION>
<OPTION <?php if ($row_grp['status'] == "A") print "selected "; ?> value="A">
<?php echo $Language->getText('admin_groupedit','active'); ?>
<OPTION <?php if ($row_grp['status'] == "P") print "selected "; ?> value="P">
<?php echo $Language->getText('admin_groupedit','pending'); ?>
<OPTION <?php if ($row_grp['status'] == "H") print "selected "; ?> value="H">
<?php echo $Language->getText('admin_groupedit','holding'); ?>
<OPTION <?php if ($row_grp['status'] == "D") print "selected "; ?> value="D">
<?php echo $Language->getText('admin_groupedit','deleted'); ?>
</SELECT>

<B><?php echo $Language->getText('admin_groupedit','public'); ?></B>
<SELECT name="form_public">
<OPTION <?php if ($row_grp['is_public'] == 1) print "selected "; ?> value="1">
<?php echo $Language->getText('global','yes'); ?>
<OPTION <?php if ($row_grp['is_public'] == 0) print "selected "; ?> value="0">
<?php echo $Language->getText('global','no'); ?>
</SELECT>

<P><B><?php echo $Language->getText('admin_groupedit','license'); ?></B>
<SELECT name="form_license">
<OPTION value="none"><?php echo $Language->getText('admin_groupedit','license_na'); ?>
<OPTION value="other"><?php echo $Language->getText('admin_groupedit','other'); ?>
<?php
	while (list($k,$v) = each($LICENSE)) {
		print "<OPTION value=\"$k\"";
		if ($k == $row_grp['license']) print " selected";
		print ">$v\n";
	}
?>
</SELECT>


<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR><?php echo $Language->getText('admin_groupedit','home_box'); ?>:
<INPUT type="text" name="form_box" value="<?php print $row_grp['unix_box']; ?>">
<BR><?php echo $Language->getText('admin_groupedit','http_domain'); ?>:
<INPUT size=40 type="text" name="form_domain" value="<?php print $row_grp['http_domain']; ?>">
<BR><INPUT type="submit" name="Update" value="<?php echo $Language->getText('global','btn_update'); ?>">
</FORM>

<P><A href="newprojectmail.php?group_id=<?php print $group_id; ?>">
<?php echo $Language->getText('admin_groupedit','send_email'); ?></A>

<?php

// ########################## OTHER INFO

print "<h3>".$Language->getText('admin_groupedit','other_info')."</h3>";
print $Language->getText('admin_groupedit','unix_grp').": $row_grp[unix_group_name]";
?>
<FORM action="?" method="POST">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<?php echo $Language->getText('admin_groupedit','rename_project_label'); ?>:
<INPUT type="text" name="new_name" value="<?php $new_name; ?>" id="new_name">
<INPUT type="submit" name="Rename" value="<?php echo $Language->getText('global','btn_update'); ?>">
</FORM>

<?php 
$currentproject->displayProjectsDescFieldsValue();

print "<h3>".$Language->getText('admin_groupedit','license_other')."</h3> $row_grp[license_other]";

$template_group = $pm->getProject($group->getTemplate());
print "<h3>".$Language->getText('admin_groupedit','built_from_template').':</h3> <a href="/projects/'.$template_group->getUnixName().'"> <B> '.$template_group->getPublicname().' </B></A>';


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
$em->processEvent("show_pending_versions", $params);
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

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$offset = $request->getValidated('offset', 'uint', 0);
if ( !$offset || $offset < 0 ) {
    $offset = 0;
}
$limit  = 50;


echo "<P><HR><P>";

echo '
<P>'.show_grouphistory ($group_id, $offset, $limit);

site_admin_footer(array());

?>
