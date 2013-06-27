<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//
//
//  Written for Codendi by Nicolas Guerin
//

require_once('pre.php');
require_once('vars.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('common/reference/ReferenceManager.class.php');
require_once('common/event/EventManager.class.php');

$request = HTTPRequest::instance();
    
function display_service_row($group_id, $service_id, $label, $short_name, $description, $is_active, $is_used, $scope, $rank, &$row_num, $su, $is_template) {
  global $Language;

    // Normal projects should not see inactive services.
    if (!$su) {
        if (!$is_active) return;
    }

    if ($service_id==100) return; // 'None' service

    if ($description == "service_".$short_name."_desc_key") {
      $description = $Language->getText('project_admin_editservice',$description);
    }
    elseif(preg_match('/(.*):(.*)/', $description, $matches)) {
        if ($Language->hasText($matches[1], $matches[2])) {
            $description = $Language->getText($matches[1], $matches[2]);
        }
    }

    if ($label == "service_".$short_name."_lbl_key") {
      $label = $Language->getText('project_admin_editservice',$label);
    }
    elseif(preg_match('/(.*):(.*)/', $label, $matches)) {
        if ($Language->hasText($matches[1], $matches[2])) {
            $label = $Language->getText($matches[1], $matches[2]);
        }
    }
    $hp =& Codendi_HTMLPurifier::instance();
    $description = $hp->purify($description);
    $label = $hp->purify($label);

    echo '<TR class="'. util_get_alt_row_color($row_num) .'">
            <TD>
              <a href="/project/admin/editservice.php?group_id='.$group_id.'&service_id='.$service_id.'" title="'.$description.'">'.$label.'</TD>';
    if ($is_template) {
        echo '<td align="center">';
        switch($short_name) {
        case 'docman':
        case 'file':
        case 'forum':
        case 'salome':
        case 'cvs':
        case 'tracker':
            echo $Language->getText('project_admin_editservice','conf_inherited_yes');;
            break;
        case 'svn':
        case 'admin':
            echo $Language->getText('project_admin_editservice','conf_inherited_partially');;
            break;
        default:
            echo $Language->getText('project_admin_editservice','conf_inherited_no');;
        }
        echo '</td>';
    }
    if ($group_id==100) {
        echo '<TD align="center">'.( $is_active ? $Language->getText('project_admin_editservice','available') : $Language->getText('project_admin_servicebar','unavailable') ).'</TD>';
    }
    
    #echo '<TD align="center">'.( $is_used ? 'Yes' : 'No' ).'</TD>';
    echo '<TD align="center">'.( $is_used ? $Language->getText('project_admin_editservice','enabled') : ( $is_active ? '<i>'.$Language->getText('project_admin_servicebar','disabled').'</i>' : '-' ) ).'</TD>';
    if ($group_id==100) {
        echo'<TD align="center">'.$scope.'</TD>';
    }
    echo '<TD align="center">'.$rank.'</TD>';
 
    if ((($scope!="system")&&($label!=$Language->getText('project_admin_servicebar','home_page')))||($group_id==100)) {
        if ($short_name) {
            $short= "&short_name=$short_name";
        } else $short='';
        echo '<TD align="center"><A HREF="?group_id='.$group_id.'&service_id='.$service_id.'&func=delete'.$short.'" onClick="return confirm(\'';
        if ($group_id==100) {
             echo $Language->getText('project_admin_servicebar','warning_del_s',$label);
       } else {
            echo $Language->getText('project_admin_servicebar','del_s');
        }
        echo '\')"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>';
    } else {
        echo '<TD align="center">-</TD>';
    }
    echo '</TR>';
    $row_num++;
}




session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$pm = ProjectManager::instance();
$project=$pm->getProject($group_id);


$is_superuser=false;
if (user_is_super_user()) {
    $is_superuser=true;
}

$is_used = $request->getValidated('is_used', 'uint', false);
$func    = $request->getValidated('func', 'string', '');

if ($func=='delete') {
    $service_id = $request->getValidated('service_id', 'uint', 0);
    // Delete service
     if (!$service_id) {
        $feedback .= ' '.$Language->getText('project_admin_servicebar','s_id_not_given').' ';
    } else {

	$sql = "DELETE FROM service WHERE group_id=$group_id AND service_id=".db_ei($service_id);

	$result=db_query($sql);
	if (!$result || db_affected_rows($result) < 1) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo','upd_fail',(db_error() ? db_error() : ' ' )));
	} else {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_del'));
	}
        if ($group_id==100) {
            $short_name = $request->getValidated('short_name', 'string', '');
            if (!$short_name) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_servicebar','cant_delete_s_from_p'));
            } else {
                // Delete service from all projects
                $sql = "DELETE FROM service WHERE short_name='".db_es($short_name)."'";
                $result=db_query($sql);
                if (!$result || db_affected_rows($result) < 1) {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_servicebar','del_fail',db_error()));
                } else {
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_del_from_p',db_affected_rows($result)));
                }
            }
	}
    }
    $GLOBALS['Response']->redirect('/project/admin/servicebar.php?group_id='.$group_id);
}

if (($func=='do_create')||($func=='do_update')) {
    $short_name  = $request->getValidated('short_name', 'string', '');
    $label       = $request->getValidated('label', 'string', '');
    $description = $request->getValidated('description', 'string', '');
    $link        = $request->getValidated('link', 'string', '');
    $rank        = $request->getValidated('rank', 'int', 500);
    $is_active   = $request->getValidated('is_active', 'uint', 0);
    // Sanity check
    if (!$label) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','label_missed'));
    }
    if (!$link) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','link_missed'));
    }
    
    $summary_rank = $project->service_data_array['summary']['rank'];
   
    if ($short_name!='summary'){
        if($rank <= $summary_rank){
            exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','bad_rank', $summary_rank));
        }
        if (!$rank) {
            exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','rank_missed'));
        }
    }
    
    if (($group_id==100)&&(!$short_name)) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_make_s'));
    }

    if (!$is_active) {
        if ($is_used) {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','set_stat_unused'));
            $is_used=false;
        }
    }
    // Substitute variables in link
    if ($group_id!=100) {
        // NOTE: if you change link variables here, change them also below, and  in src/common/project/RegisterProjectStep_Confirmation.class.php and src/www/include/Layout.class.php
        if (strstr($link,'$projectname')) {
            // Don't check project name if not needed.
            // When it is done here, the service bar will not appear updated on the current page
            $link=str_replace('$projectname',$project->getUnixName(),$link);
        }
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        if ($GLOBALS['sys_force_ssl']) {
            $sys_default_protocol='https'; 
        } else { $sys_default_protocol='http'; }
        $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
        $link=str_replace('$group_id',$group_id,$link);
    }

}

$scope = $request->getValidated('scope', 'string', '');

if ($func=='do_create') {

    if ($short_name) {
        // Check that the short_name is not already used
        $sql="SELECT * FROM service WHERE short_name='".db_es($short_name)."'";
        $result=db_query($sql);
        if (db_numrows($result)>0) {
            exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','short_name_exist'));
        }
    } 

    if (($group_id!=100)&&($scope=="system")) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_make_system_wide_s'));
    }
    $is_in_iframe = $request->get('is_in_iframe') ? 1 : 0;
    // Create
    $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank, is_in_iframe) VALUES ($group_id, '$label', '$description', '$short_name', '$link', ".($is_active?"1":"0").", ".($is_used?"1":"0").", '$scope', $rank, $is_in_iframe)";
    $result=db_query($sql);

    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_create_s',db_error()));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_create_success'));
    }
    
    $pm->clear($group_id);
    $project = $pm->getProject($group_id);
    
    if (($is_active)&&($group_id==100)) {
        // Add service to ALL active projects!
        $sql1="SELECT group_id FROM groups WHERE group_id!=100";
        $result1=db_query($sql1);
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
        $nbproj=1;
        if ($GLOBALS['sys_force_ssl']) {
            $sys_default_protocol='https'; 
        } else { $sys_default_protocol='http'; }
        while ($arr = db_fetch_array($result1)) {
            $my_group_id=$arr['group_id'];
            // Substitute values in links
            $my_link=$link;
            if (strstr($link,'$projectname')) {
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $pm = ProjectManager::instance();
                $my_link=str_replace('$projectname',$pm->getProject($my_group_id)->getUnixName(),$my_link);
            }
            $my_link=str_replace('$group_id',$my_group_id,$my_link);

            $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank, is_in_iframe) VALUES ($my_group_id, '$label', '$description', '$short_name', '$my_link', ".($is_active?"1":"0").", ".($is_used?"1":"0").", '$scope', $rank, $is_in_iframe)";
            $result=db_query($sql);
            $nbproj++;
            if (!$result) {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_servicebar','cant_create_s_for_p',$my_group_id));
            }
        }
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_add_success',$nbproj));
    }
    $GLOBALS['Response']->redirect('/project/admin/servicebar.php?group_id='.$group_id);
}

if ($func=='do_update') {
    $service_id = $request->getValidated('service_id', 'uint', 0);
    if (!$service_id) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','s_id_missed'));
    }
    $set_server_id = '';
    $server_id = $request->getValidated('server_id', 'uint');
    if (user_is_super_user() && $server_id) {
        $set_server_id = ", location = 'satellite', server_id = ". (int)$server_id .' ';
    }
    $is_in_iframe = $request->get('is_in_iframe') ? 1 : 0;
    $admin_statement = '';
    if (user_is_super_user()) { //is_active and scope can only be change by a siteadmin
        $admin_statement = ", is_active=". ($is_active ? 1 : 0) .", scope='". $scope ."'";
        
    }

    if (isset($short_name)) {
        // Store current 'is_used' value for this service
        $previous_is_used=$project->usesService($short_name);
    }
    $sql = "UPDATE service SET label='$label', description='$description', link='$link' ". $admin_statement .
        ", is_used=".($is_used?"1":"0").", rank='$rank' $set_server_id, is_in_iframe=$is_in_iframe WHERE service_id=$service_id";
    $result=db_query($sql);
   
    if (!$result) {
        exit_error($Language->getText('global','error'),$Language->getText('project_admin_servicebar','cant_update_s',db_error()));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_servicebar','s_update_success'));
    }
    
    $pm->clear($group_id);
    $project =$pm->getProject($group_id);

    // If this is a global service (i.e. with a shortname)... 
    if (isset($short_name)) {
        // And if usage was changed
        if ( $previous_is_used != $is_used ) {
            //... we might need to (de-)activate the corresponding reference
            $reference_manager =& ReferenceManager::instance();
            $reference_manager->updateReferenceForService($group_id,$short_name,($is_used?"1":"0"));
            
            //... and let plugins do what they have to do.
            $em =& EventManager::instance();
            $em->processEvent('service_is_used', array('shortname' => $short_name, 'is_used' => $is_used?true:false, 'group_id' => $group_id));
        }
    }
    $GLOBALS['Response']->redirect('/project/admin/servicebar.php?group_id='.$group_id);
}

project_admin_header(array('title'=>$Language->getText('project_admin_servicebar','edit_s_bar'),'group'=>$group_id,
			   'help' => 'ServiceConfiguration.html'));

if ($group_id==100) {
    print '<P><h2>'.$Language->getText('project_admin_servicebar','edit_system_s').'</B></h2>';
} else {
    print '<P><h2>'.$Language->getText('project_admin_servicebar','edit_s_for',$project->getPublicName()).'</h2>';
}
print '
<P>
<H3>'.$Language->getText('project_admin_servicebar','new_s').'</H3>
<a href="/project/admin/editservice.php?func=create&group_id='.$group_id.'">'.$Language->getText('project_admin_servicebar','create_s').'</a>
<p>


<H3>'.$Language->getText('project_admin_servicebar','manage_s').'</H3>
<P>
';
/*
	Show the options that this project is using
*/
echo '
<HR>
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>';

$title_arr=array();
$title_arr[]=$Language->getText('project_admin_editservice','s_label');
if ($project->isTemplate()) {
    $title_arr[]=$Language->getText('project_admin_editservice','conf_inherited');
}
if ($group_id==100) {
    $title_arr[]=$Language->getText('project_admin_servicebar','availability');
}
$title_arr[]=$Language->getText('global','status');
if ($group_id==100) {
    $title_arr[]=$Language->getText('project_admin_editservice','scope');
}
$title_arr[]=$Language->getText('project_admin_servicebar','rank_on_screen');
$title_arr[]=$Language->getText('project_admin_servicebar','del?');
echo html_build_list_table_top($title_arr);


$result = db_query("SELECT * FROM service WHERE group_id=$group_id ORDER BY rank");
if (db_numrows($result) < 1) {
	exit_no_group();
}
$row_num=0;
while ($serv = db_fetch_array($result)) {
    $classname = $project->getServiceClassName($serv['short_name']);
    try {
        $s = new $classname($project, $serv);
        display_service_row(
            $group_id,
            $serv['service_id'],
            $serv['label'],
            $serv['short_name'],
            $serv['description'],
            $serv['is_active'],
            $serv['is_used'],
            $serv['scope'],
            $serv['rank'],
            $row_num,
            $is_superuser,
            $project->isTemplate()
        );
    } catch (ServiceNotAllowedForProjectException $e) {
        //don't display the row for this servce
    }
}


echo '
</TABLE>
';




project_admin_footer(array());


 

?>
