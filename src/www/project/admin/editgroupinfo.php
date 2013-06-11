<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('vars.php');
require_once('www/project/admin/project_admin_utils.php');


$group_id=$request->get('group_id');
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$descfieldsinfos = getProjectsDescFieldsInfos();
$currentproject= new project($group_id);
$descfieldsvalue=$currentproject->getProjectsDescFieldsValue();

// If this was a submission, make updates

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id=". db_ei($group_id));
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);


$form_group_name = trim($request->get('form_group_name'));
$form_shortdesc =$request->get('form_shortdesc');
$Update=$request->get('Update');

$valid_data=0;
if($Update){
	//data validation
	$valid_data=1;
	if (!$form_group_name||!$form_shortdesc) {
	    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'info_missed'));
		$valid_data=0;
    } else {
        $rule = new Rule_ProjectFullName();
        if (!$rule->isValid($form_group_name)) {
            $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            $valid_data=0;
        }
    }

	$descfieldsinfos = getProjectsDescFieldsInfos();
	for($i=0;$i<sizeof($descfieldsinfos);$i++){
	    	$currentform=trim($request->get("form_".$descfieldsinfos[$i]["group_desc_id"]));
			
		if ( ($descfieldsinfos[$i]['desc_required']==1) && (!$currentform) ) {
	    	$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'info_missed'));
		    $valid_data=0;
		}
	}
}
    

if ($valid_data==1) {
	
	// insert descriptions 
	$updatedesc=0;
	for($i=0;$i<sizeof($descfieldsinfos);$i++){
		
		$currentform=trim($request->get("form_".$descfieldsinfos[$i]["group_desc_id"]));
		
		
		
		for($j=0;$j<sizeof($descfieldsvalue);$j++){
		
			if($descfieldsvalue[$j]['group_desc_id']==$descfieldsinfos[$i]['group_desc_id']){
				$previousvalue[$i]=$descfieldsvalue[$j]['value'];
			}	
		}
		
		
		if($currentform!=''){
			
			if(isset($previousvalue[$i])&&($previousvalue[$i]!=$currentform)){
				
				$sql='UPDATE group_desc_value SET '
						."value='".db_escape_string($currentform)."'";
				$sql .=" WHERE group_id=". db_ei($group_id) ." AND group_desc_id='".db_ei($descfieldsinfos[$i]["group_desc_id"])."'";
				
				$resultdesc[$i]=db_query($sql);
				if($resultdesc[$i] || db_affected_rows($resultdesc[$i]) >= 1){
					$updatedesc=1;
				}
				
			}else if(!isset($previousvalue[$i])){
				$sql="INSERT INTO group_desc_value (group_id, group_desc_id, value) VALUES"
					 ." ('".db_ei($group_id)."','".db_ei($descfieldsinfos[$i]["group_desc_id"])."','".db_escape_string($currentform)."')";
				$resultdesc[$i]=db_query($sql);
				if($resultdesc){
					$updatedesc=1;
				}	
			}
								
		}else{
			if(isset($previousvalue[$i])){	
				$sql="DELETE FROM group_desc_value WHERE group_id=". db_ei($group_id) ." AND group_desc_id='".db_ei($descfieldsinfos[$i]["group_desc_id"])."'";
				$resultdesc[$i]=db_query($sql);
				if($resultdesc){
					$updatedesc=1;
				}	
			}			
		}
	}	
    // in the database, these all default to '1', 
    // so we have to explicity set 0
    
    $sql = 'UPDATE groups SET '
        ."group_name='".db_es(htmlspecialchars($form_group_name))."',"
        ."short_description='". db_es($form_shortdesc) ."'";
		
    $sql .= " WHERE group_id='".db_ei($group_id)."'";

    //echo $sql;
    $result=db_query($sql);
    
    if ((!$result || db_affected_rows($result) < 1)&&($updatedesc==0)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' )));
    } else {
    	
    	
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_editgroupinfo','upd_success'));
        group_add_history('changed_public_info','',$group_id);
        
        // Raise an event
        $em =& EventManager::instance();
        $em->processEvent('project_admin_edition', array(
            'group_id'       => $group_id
        ));
    
    }
    
    
    
}

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id='".db_ei($group_id)."'");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);
$descfieldsvalue=$currentproject->getProjectsDescFieldsValue();


project_admin_header(array('title'=>$Language->getText('project_admin_editgroupinfo','editing_g_info'),'group'=>$group_id,
			   'help' => 'ProjectPublicInformation.html'));

print '<P><h3>'.$Language->getText('project_admin_editgroupinfo','editing_g_info_for',$row_grp['group_name']).'</h3>';

$hp = Codendi_HTMLPurifier::instance();
print '
<P>
<FORM action="?" method="post">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">

<P>'.$Language->getText('project_admin_editgroupinfo','descriptive_g_name').'<font color="red">*</font>
<BR><INPUT type="text" size="50" maxlen="40" name="form_group_name" value="'. $hp->purify(util_unconvert_htmlspecialchars($row_grp['group_name']), CODENDI_PURIFIER_CONVERT_HTML) .'">

<P>'.$Language->getText('project_admin_editgroupinfo','short_desc').'<font color="red">*</font>
<BR><TEXTAREA cols="70" rows="3" wrap="virtual" name="form_shortdesc">
'. $hp->purify(util_unconvert_htmlspecialchars($row_grp['short_description']), CODENDI_PURIFIER_CONVERT_HTML) .'</TEXTAREA>';


for($i=0;$i<sizeof($descfieldsinfos);$i++){

	for($j=0;$j<sizeof($descfieldsvalue);$j++){
		
		if($descfieldsvalue[$j]['group_desc_id']==$descfieldsinfos[$i]['group_desc_id']){
			$displayfieldvalue[$i]=$descfieldsvalue[$j]['value'];
		}	
	}
	
	$descname=$descfieldsinfos[$i]["desc_name"];
	if(preg_match('/(.*):(.*)/', $descname, $matches)) {
		
		if ($Language->hasText($matches[1], $matches[2])) {
    		$descname = $Language->getText($matches[1], $matches[2]);
		}
	}
    		
	echo "<P><u>".$hp->purify($descname,CODENDI_PURIFIER_LIGHT,$group_id);
	if($descfieldsinfos[$i]["desc_required"]==1){
		echo "<font color='red'>*</font>";
	}
	echo ":</u>";
	if($descfieldsinfos[$i]["desc_type"]=='line'){
		
		echo "<BR><INPUT type='text' size='70' maxlen='70' name='form_".$descfieldsinfos[$i]["group_desc_id"];
			 
		if(isset($displayfieldvalue[$i])){
			echo "' value='".$hp->purify($displayfieldvalue[$i],CODENDI_PURIFIER_CONVERT_HTML,$group_id);
		}
		
		echo "'></BR>" ;
		
	}else if($descfieldsinfos[$i]["desc_type"]=='text'){
		
		echo "<BR><TEXTAREA name='form_".$descfieldsinfos[$i]["group_desc_id"].
			 "' wrap='virtual' cols='70' rows='8'>";
			 
		if(isset($displayfieldvalue[$i])){
			echo $hp->purify($displayfieldvalue[$i],CODENDI_PURIFIER_CONVERT_HTML,$group_id);
		}
		echo "</TEXTAREA></BR>" ;
	}
	echo "</P>";
}

echo '
<p>
    <u>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo','parent_project').'</u>
    <br/>
    <input type="text" name="parent_project" value="" size ="50" id="parent_project" />
</p>';

$js = "new ProjectAutoCompleter('parent_project', '".util_get_dir_image_theme()."', false);";
$GLOBALS['HTML']->includeFooterJavascriptSnippet($js);

echo '
<P><INPUT type="submit" name="Update" value="'.$Language->getText('global','btn_update').'">
</FORM>
';

project_admin_footer(array());

?>
