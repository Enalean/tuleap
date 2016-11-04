<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright Enalean (c) 2015. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
require_once('vars.php');
require_once('www/project/admin/project_admin_utils.php');

use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\DescriptionFieldsDao;

$group_id=$request->get('group_id');
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$fields_factory  = new DescriptionFieldsFactory(new DescriptionFieldsDao());
$descfieldsinfos = $fields_factory->getAllDescriptionFields();
$currentproject  = new project($group_id);
$descfieldsvalue = $currentproject->getProjectsDescFieldsValue();

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

        $descfieldsinfos = $fields_factory->getAllDescriptionFields();
	for($i=0;$i<sizeof($descfieldsinfos);$i++){
	    	$currentform=trim($request->get("form_".$descfieldsinfos[$i]["group_desc_id"]));
			
		if ( ($descfieldsinfos[$i]['desc_required']==1) && (!$currentform) ) {
	    	$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'info_missed'));
		    $valid_data=0;
		}
	}
}
    
$project_manager = ProjectManager::instance();
$current_user = $request->getCurrentUser();

$user_can_choose_visibility       = $current_user->isSuperUser() || ForgeConfig::get(ForgeAccess::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY);
$user_can_choose_truncated_emails = $current_user->isSuperUser();

$set_parent = false;
$valid_parent = true;
if ($valid_data==1) {
	
	// insert descriptions 
	$updatedesc    = 0;
	$previousvalue = array();
	$resultdesc    = array();
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

        /*
         * Setting parent project
         */
        try {
            if ($request->existAndNonEmpty('parent_project')) {
                $parent_project = $project_manager->getProjectFromAutocompleter($request->get('parent_project'));
                if ($parent_project && $current_user->isMember($parent_project->getId(), 'A')) {
                    $set_parent = $project_manager->setParentProject($group_id, $parent_project->getID());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo', 'must_be_admin_of_parent_project'));
                    $valid_parent = false;
                }
            }
            if ($request->existAndNonEmpty('remove_parent_project')) {
                $set_parent = $project_manager->removeParentProject($group_id);
            }
        } catch (Project_HierarchyManagerNoChangeException $e) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' )));
            $valid_parent = false;
        } catch (Project_HierarchyManagerAncestorIsSelfException $e) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','self_exception',(db_error() ? db_error() : ' ' )));
            $valid_parent = false;
        } catch (Project_HierarchyManagerAlreadyAncestorException $e) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','ancestor_exception',(db_error() ? db_error() : ' ' )));
            $valid_parent = false;
        }

    // in the database, these all default to '1', 
    // so we have to explicity set 0
    
    $sql = 'UPDATE groups SET '
        ."group_name='".db_es(htmlspecialchars($form_group_name))."',"
        ."short_description='". db_es($form_shortdesc) ."'";
		
    $sql .= " WHERE group_id='".db_ei($group_id)."'";

    //echo $sql;
    $result=db_query($sql);

    $update_success = true;
    if ((! $result || db_affected_rows($result) < 1) && ($updatedesc==0) && ! $set_parent) {
        $update_success = false;
    } else {
        group_add_history('changed_public_info','',$group_id);
        
        // Raise an event
        $em =& EventManager::instance();
        $em->processEvent('project_admin_edition', array(
            'group_id'       => $group_id
        ));
    }

    //update visibility
    if ($user_can_choose_visibility) {
        if ($currentproject->getAccess() != $request->get('project_visibility')) {
            $project_manager->setAccess($currentproject, $request->get('project_visibility'));
            $update_success = true;
        }
    }

    //update truncated emails
    if ($user_can_choose_truncated_emails) {
        $usage = (int) $request->exist('truncated_emails');
        if ($currentproject->getTruncatedEmailsUsage() != $usage) {
            $project_manager->setTruncatedEmailsUsage($currentproject, $usage);
            $update_success = true;
        }
    }

    if (! $update_success) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' )));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_editgroupinfo','upd_success'));
    }
}

$project_manager->clearProjectFromCache($currentproject->getID());
$currentproject = $project_manager->getProject($currentproject->getID());

// update info for page
$res_grp = db_query("SELECT * FROM groups WHERE group_id='".db_ei($group_id)."'");
if (db_numrows($res_grp) < 1) {
	exit_no_group();
}
$row_grp = db_fetch_array($res_grp);
$descfieldsvalue=$currentproject->getProjectsDescFieldsValue();


project_admin_header(array('title'=>$Language->getText('project_admin_editgroupinfo','editing_g_info'),'group'=>$group_id,
			   'help' => 'project-admin.html#project-public-information'));

echo '<FORM action="?group_id='.$group_id.'" method="post" id="project_info_form">';

$renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/project/');

if ($user_can_choose_visibility) {
    $presenter = new ProjectVisibilityPresenter($Language, ForgeConfig::areRestrictedUsersAllowed(), $currentproject->getAccess());
    echo $renderer->renderToString('project_visibility', $presenter);
}

if ($user_can_choose_truncated_emails) {
    $truncated_mails_impacted_services = array();

    $file_service = $currentproject->getService(Service::FILE);
    if ($file_service) {
        $truncated_mails_impacted_services[] = $file_service->getInternationalizedName();
    }

    $svn_service  = $currentproject->getService(Service::SVN);
    if ($svn_service) {
        $truncated_mails_impacted_services[] = $svn_service->getInternationalizedName();
    }

    $wiki_service = $currentproject->getService(Service::WIKI);
    if ($wiki_service) {
        $truncated_mails_impacted_services[] = $wiki_service->getInternationalizedName();
    }

    EventManager::instance()->processEvent(
        Event::SERVICES_TRUNCATED_EMAILS,
        array(
            'project'  => $currentproject,
            'services' => &$truncated_mails_impacted_services
        )
    );
    $presenter = new ProjectTruncatedEmailsPresenter($currentproject, $truncated_mails_impacted_services);
    echo $renderer->renderToString('truncated_emails', $presenter);
}

print "<P><h3>".$Language->getText('project_admin_editgroupinfo','editing_g_info_for',$row_grp['group_name']).'</h3>';

$hp = Codendi_HTMLPurifier::instance();
print '
<P>
<P>'.$Language->getText('project_admin_editgroupinfo','descriptive_g_name').'<font color="red">*</font>
<BR><INPUT type="text" size="50" maxlen="40" name="form_group_name" value="'. $hp->purify(util_unconvert_htmlspecialchars($row_grp['group_name']), CODENDI_PURIFIER_CONVERT_HTML) .'">

<P>'.$Language->getText('project_admin_editgroupinfo','short_desc').'<font color="red">*</font>
<BR><TEXTAREA cols="70" rows="3" wrap="virtual" name="form_shortdesc">
'. $hp->purify(util_unconvert_htmlspecialchars($row_grp['short_description']), CODENDI_PURIFIER_CONVERT_HTML) .'</TEXTAREA>';

$displayfieldvalue = array();
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

echo '<h3>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo', 'project_hierarchy_title').'</h3>';
echo '<p>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo', 'project_hierarchy_desc_1').'</p>';
echo '<p>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo', 'project_hierarchy_desc_2').'</p>';
echo '<p><strong>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo', 'project_hierarchy_desc_3').'</strong></p>';

echo '
<p>
    <u>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo','parent_project').'</u>
    <br/> ';

$parent = $project_manager->getParentProject($group_id);
if ($parent) {
    $parent_name = $parent->getUnixName();

    if ($current_user->isMember($parent->getId(), 'A')) {
        $url = '?group_id='.$parent->getID();
    } else {
        $url = '/projects/'.$parent->getUnixName();
    }

    echo '<a href="'.$url.'"> '.$parent_name.' </a>
    <br/>
    <label><input type="checkbox" name="remove_parent_project"/>'.$GLOBALS['Language']->getText('project_admin_editgroupinfo','remove_parent_project').'</label>';
} else {
     echo '<input type="text" name="parent_project" size ="50" id="parent_project" /><br/>';
}
echo '</p>';

$js = "new ProjectAutoCompleter('parent_project', '".util_get_dir_image_theme()."', false, {'allowNull' : true});";
$GLOBALS['HTML']->includeFooterJavascriptSnippet($js);

echo "<u>".$GLOBALS['Language']->getText('project_admin_editgroupinfo', 'sub_projects')."</u><br>";
$children = $project_manager->getChildProjects($group_id);

foreach ($children as $child) {
    if ($current_user->isMember($child->getId(), 'A')) {
        $url = '?group_id='.$child->getID();
    } else {
        $url = '/projects/'.$child->getUnixName();
    }
    echo '<a href="'.$url.'">'.$child->getPublicName() . '</a> ';
}

echo '
<P><br><INPUT type="submit" name="Update" value="'.$Language->getText('global','btn_update').'"></P>
</FORM>
';

project_admin_footer(array());

?>
