<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2004. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Nicolas Guérin
//

require ('pre.php');
require ('vars.php');
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');


function display_service_row($group_id, $service_id, $label, $short_name, $description, $is_active, $is_used, $scope, $rank, &$row_num, $su) {

    // Normal projects should not see inactive services.
    if (!$su) {
        if (!$is_active) return;
    }

    if ($service_id==100) return; // 'None' service

    echo '<TR class="'. util_get_alt_row_color($row_num) .'">
            <TD>
              <a href="/project/admin/editservice.php?group_id='.$group_id.'&service_id='.$service_id.'" title="'.$description.'">'.$label.'</TD>';
    
    if ($group_id==100) {
        echo '<TD align="center">'.( $is_active ? 'Available' : 'Unavailable' ).'</TD>';
    }
    
    #echo '<TD align="center">'.( $is_used ? 'Yes' : 'No' ).'</TD>';
    echo '<TD align="center">'.( $is_used ? 'Enabled' : ( $is_active ? '<i>Disabled</i>' : '-' ) ).'</TD>';
    if ($group_id==100) {
        echo'<TD align="center">'.$scope.'</TD>';
    }
    echo '<TD align="center">'.$rank.'</TD>';
 
    if ((($scope!="system")&&($label!='Home Page'))||($group_id==100)) {
        if ($short_name) {
            $short= "&short_name=$short_name";
        } else $short='';
        echo '<TD align="center"><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&service_id='.$service_id.'&func=delete'.$short.'" onClick="return confirm(\'';
        if ($group_id==100) {
             echo '*********** WARNING ***********\n';
             echo ' Do you want to delete the service?\n';
             echo ' NOTE: this will remove this service ('.$label.') from ALL projects of this server.\n';
             echo ' Are you SURE you want to continue ?';
       } else {
            echo 'Delete this service ?';
        }
        echo '\')"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>';
    } else {
        echo '<TD align="center">-</TD>';
    }
    echo '</TR>';
    $row_num++;
}




session_require(array('group'=>$group_id,'admin_flags'=>'A'));


$is_superuser=false;
if (user_is_super_user()) {
    $is_superuser=true;
}

if ($func=='delete') {

    // Delete service
     if (!$service_id) {
        $feedback .= ' FAILED: Service Id was not specified ';
    } else {

	$sql = "DELETE FROM service WHERE group_id=$group_id AND service_id=$service_id";

	$result=db_query($sql);
	if (!$result || db_affected_rows($result) < 1) {
		$feedback .= ' UPDATE FAILED OR NO DATA CHANGED! '.db_error();
	} else {
		$feedback .= ' Service Deleted ';
	}
        if ($group_id==100) {
            if (!$short_name) {
		$feedback .= ' - Cannot delete service from all projects: service shortname is missing ';
            } else {
                // Delete service from all projects
                $sql = "DELETE FROM service WHERE short_name='$short_name'";
                $result=db_query($sql);
                if (!$result || db_affected_rows($result) < 1) {
                    $feedback .= ' - DELETE FAILED! '.db_error();
                } else {
                    $feedback .= ' - Service Deleted from '.db_affected_rows($result).' projects ';
                }
            }
	}
    }
}

if (($func=='do_create')||($func=='do_update')) {
    // Sanity check
    if (!$label) {
        exit_error("ERROR",'The label is missing, please press the "Back" button and complete this information');
    }
    if (!$link) {
        exit_error("ERROR",'The link is missing, please press the "Back" button and complete this information');
    }
    if (($group_id==100)&&(!$short_name)) {
        exit_error("ERROR",'Cannot make system-wide service without short name, please press the "Back" button and complete this information');
    }

    if (!$is_active) {
        if ($is_used) {
            $feedback .= '- Setting status to "unused" -';
            $is_used=false;
        }
    }
    // Substitute variables in link
    if ($group_id!=100) {
        // NOTE: if you change link variables here, change them also below, and  in SF/www/register/confirmation.php and SF/www/include/Layout.class
        if (strstr($link,'$projectname')) {
            // Don't check project name if not needed.
            // When it is done here, the service bar will not appear updated on the current page
            $link=str_replace('$projectname',group_getunixname($group_id),$link);
        }
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        $link=str_replace('$group_id',$group_id,$link);
    }

}

if ($func=='do_create') {

    if ($short_name) {
        // Check that the short_name is not already used
        $sql="SELECT * FROM service WHERE short_name='$short_name'";
        $result=db_query($sql);
        if (db_numrows($result)>0) {
            exit_error("ERROR","A service with this short name ($short_name) already exists. Please press the 'Back' button and change this information");
        }
    } 

    if (($group_id!=100)&&($scope=="system")) {
        exit_error("ERROR",'Cannot make system-wide service in project other than 100, please press the "Back" button and change this information');
    }

    // Create
    $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ($group_id, '$label', '$description', '$short_name', '$link', ".($is_active?"1":"0").", ".($is_used?"1":"0").", '$scope', $rank)";
    $result=db_query($sql);

    if (!$result) {
        exit_error("ERROR",'ERROR - Can not create service: '.db_error());
    } else {
        $feedback .= " Successfully Created Service ";
    }

    if (($is_active)&&($group_id==100)) {
        // Add service to ALL active projects!
        $sql1="SELECT group_id FROM groups WHERE group_id!=100";
        $result1=db_query($sql1);
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        $nbproj=1;
        while ($arr = db_fetch_array($result1)) {
            $my_group_id=$arr['group_id'];
            // Substitute values in links
            $my_link=$link;
            if (strstr($link,'$projectname')) {
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $my_link=str_replace('$projectname',group_getunixname($my_group_id),$my_link);
            }
            $my_link=str_replace('$group_id',$my_group_id,$my_link);

            $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ($my_group_id, '$label', '$description', '$short_name', '$my_link', ".($is_active?"1":"0").", ".($is_used?"1":"0").", '$scope', $rank)";
            $result=db_query($sql);
            $nbproj++;
            if (!$result) {
                $feedback .= ' ERROR - Can not create service for project '.$my_group_id;
            }
        }
        $feedback .= " - Successfully Added Service to $nbproj Projects";
    }
}

if ($func=='do_update') {
    if (!$service_id) {
        exit_error("ERROR",'The service ID is missing');
    }
    $sql = "UPDATE service SET label='$label', description='$description', link='$link', is_active=".($is_active?"1":"0").
        ", is_used=".($is_used?"1":"0").", scope='$scope', rank='$rank' WHERE service_id=$service_id";
    $result=db_query($sql);

    if (!$result) {
        exit_error("ERROR",'ERROR - Can not update service: '.db_error());
    } else {
        $feedback .= " Successfully Updated Service ";
    }

}



$project=project_get_object($group_id);

project_admin_header(array('title'=>'Editing Service Bar','group'=>$group_id,
			   'help' => 'ServiceConfiguration.html'));

if ($group_id==100) {
    print '<P><h2>Editing System Services</B></h2>';
} else {
    print '<P><h2>Editing Services for <B>'.$project->getPublicName().'</B></h2>';
}
print '
<P>
<H3>New Service</H3>
<a href="/project/admin/editservice.php?func=create&group_id='.$group_id.'">Create a new service.</a>
<p>


<H3>Manage Services:</H3>
<P>
';
/*
	Show the options that this project is using
*/
echo '
<HR>
<TABLE width="100%" cellspacing=0 cellpadding=3 border=0>';

$title_arr=array();
$title_arr[]='Service Label';
if ($group_id==100) {
    $title_arr[]='Availability';
}
$title_arr[]='Status';
if ($group_id==100) {
    $title_arr[]='Scope';
}
$title_arr[]='Rank On Screen';
$title_arr[]='Delete?';
echo html_build_list_table_top($title_arr);


$result = db_query("SELECT * FROM service WHERE group_id=$group_id ORDER BY rank");
if (db_numrows($result) < 1) {
	exit_no_group();
}
$row_num=0;
while ($serv = db_fetch_array($result)) {
    display_service_row($group_id,$serv['service_id'],$serv['label'],$serv['short_name'],$serv['description'],$serv['is_active'],$serv['is_used'],$serv['scope'],$serv['rank'],$row_num,$is_superuser);
}


echo '
</TABLE>
';




project_admin_footer(array());


 

?>
