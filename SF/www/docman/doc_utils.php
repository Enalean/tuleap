<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

/*
	Docmentation Manager
	by Quentin Cregan, SourceForge 06/2000
*/

$Language->loadLanguageMsg('docman/docman');

function display_groups_option($group_id=false,$checkedval='xyxy') {

    if (!$group_id) {
	exit_no_group();
    } else {
	$query = "select doc_group, groupname "
	    ."from doc_groups "
	    ."where group_id = $group_id "
	    ."order by groupname";
	$result = db_query($query);

	echo html_build_select_box ($result,'doc_group',$checkedval);

    } //end else

} //end display_groups_option


function groups_defined($group_id) {
	// return true if a group other than None is defined
	$query = "select * "
		."from doc_groups "
		."where group_id = '$group_id'";
	$result = db_query($query);
	
	if (db_numrows($result) < 1) {
	  return false;
	}
	return true;
}


function display_groups($group_id) {
    global $Language;

    // show list of groups to edit.
    $query = "select * "
        ."from doc_groups "
        ."where group_id = '$group_id' "
        ."order by group_rank";
    $result = db_query($query);
	
    if (db_numrows($result) < 1) {
        print "<p>".$Language->getText('docman_doc_utils','error_nogroup');
    } else {

        $title_arr=array();
        $title_arr[]=$Language->getText('docman_doc_utils','group_id');
        $title_arr[]=$Language->getText('docman_doc_utils','group_name');
        $title_arr[]=$Language->getText('docman_doc_utils','rank');
        $title_arr[]=$Language->getText('docman_doc_utils','permissions');
        $title_arr[]=$Language->getText('docman_doc_utils','delete_ask');

        echo html_build_list_table_top ($title_arr);

        $i = 0;
        while ($row = db_fetch_array($result)) {
            $output = "<tr class=\"".util_get_alt_row_color($i)."\">".
                '<td><b><a href="index.php?mode=groupedit&doc_group='.$row['doc_group'].'&group_id='.$group_id.'">'.$row['doc_group']."</a></b></td>\n".
                '<td>   <a href="index.php?mode=groupedit&doc_group='.$row['doc_group']."&group_id=".$group_id.'">'.$row['groupname']."</td>\n".
                "<td>".$row['group_rank']."</td>".
                "<td align='center'><FONT SIZE='-1'><a href='/docman/admin/editdocgrouppermissions.php?doc_group=".$row['doc_group']."&group_id=$group_id'>";
            
            if (permission_exist('DOCGROUP_READ',$row['doc_group'])) {
                $output .= $Language->getText('docman_doc_utils','edit_perms');
            } else $output .= $Language->getText('docman_doc_utils','define_perms');
            
            $output .="</a></font></td>".
                '<td align="center"><a href="index.php?mode=groupdelete&doc_group='.$row['doc_group'].'&group_id='.
                $group_id.'"><img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('docman_doc_utils','delete_confirm').'\')"></A></td></tr>';
            
            print "$output";
            $i++;
        }
        echo '</table>';
    }
    docman_footer($params);
}


function display_docs($group_id) {
    global $sys_datefmt, $Language;

    $query = "select * "
        ."from doc_data as d1, doc_groups as d2 "
        ."where d2.group_id = '".$group_id."' " 
        ."and d1.doc_group = d2.doc_group "
        ."order by group_rank, rank"; 
    $result = db_query($query);

    if (db_numrows($result) < 1) {
	       
        echo $Language->getText('docman_doc_utils','error_nodocyet').'<p>';
        
    } else {

        $title_arr=array();
        $title_arr[]=$Language->getText('docman_doc_utils','doc_id');
        $title_arr[]=$Language->getText('docman_doc_utils','doc_name');
        $title_arr[]=$Language->getText('docman_doc_utils','doc_group');
        $title_arr[]=$Language->getText('docman_doc_utils','rank_in_group');
        $title_arr[]=$Language->getText('docman_doc_utils','create_date');
        $title_arr[]=$Language->getText('docman_doc_utils','permissions');
        $title_arr[]=$Language->getText('docman_doc_utils','delete_ask');
        
        echo html_build_list_table_top ($title_arr);

        $i = 0;
        while ($row = db_fetch_array($result)) {
            $edit_uri = "index.php?docid=".$row['docid']."&mode=docedit&group_id=".$group_id;
            print "<tr class=\"".util_get_alt_row_color($i)."\">"
                ."<td><b><a href=\"".$edit_uri."\">".$row['docid']."</b></a></td>"
                ."<td><a href=\"".$edit_uri."\">".$row['title']."</a></td>"
                ."<td>".$row['groupname']."</td>"
                ."<td>".$row['rank']."</td>"
                ."<td>".format_date($Language->getText('system','datefmt'),$row['createdate'])."</td>"
                ."<td align='center'><FONT SIZE='-1'><a href='/docman/admin/editdocpermissions.php?docid=".$row['docid']."&group_id=$group_id'>";
            if (permission_exist('DOCUMENT_READ',$row['docid'])) {
                print $Language->getText('docman_doc_utils','edit_perms');
            } else print $Language->getText('docman_doc_utils','define_perms');
            print "</a></font></td>"
                .'<td align="center"><a href="index.php?mode=docdelete&docid='.$row['docid'].'&group_id='.
                $group_id.'"><img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('docman_doc_utils','delete_doc_confirm').'\')"></A></td></tr>';    
            $i++;
        }	
        echo '</table>';
    }//end else
    
} //end function display_docs


function docman_header($params) {

    global $group_id,$Language;

	$project=project_get_object($group_id);
	
	if (!$project->isProject()) {
	    exit_error($Language->getText('global','error'),
		       $Language->getText('docman_doc_utils','error_proj'));
	}
	if (!$project->usesDocman()) {
	    exit_error($Language->getText('global','error'),
		       $Language->getText('docman_doc_utils','error_off'));
	}
        // There might be encoded HTML tags in the title
	site_project_header(array('title'=>strip_tags(util_unconvert_htmlspecialchars($params['title'])),'group'=>$group_id,'toptab'=>'doc','pv'=>$params['pv']));

        if (!$params['pv']) {
            print "<p><b><a href=\"/docman/new.php?group_id=".$group_id."\">".$Language->getText('docman_doc_utils','submit_doc')."</a> | ".
		"<a href=\"/docman/admin/index.php?group_id=".$group_id."\">".$Language->getText('docman_doc_utils','admin')."</a></b>"; 
	
            if ($params['help']) {
                echo ' | <b>  '.help_button($params['help'],false,$Language->getText('global','help')).'</b>';
            }
        }
}

function docman_header_admin($params) {

    global $group_id,$Language;

    $project=project_get_object($group_id);
    
    if (!$project->isProject()) {
	exit_error($Language->getText('global','error'),
		   $Language->getText('docman_doc_utils','error_proj'));
    }
    if (!$project->usesDocman()) {
	exit_error($Language->getText('global','error'),
		   $Language->getText('docman_doc_utils','error_off'));
    }
    
    site_project_header(array('title'=>$params['title'],'group'=>$group_id,'toptab'=>'doc'));
    
    print "<b><a href=\"/docman/admin/index.php?group_id=".$group_id."\">".$Language->getText('docman_doc_utils','admin')."</a></b>"; 
    print "<b>  | <a href=\"/docman/admin/index.php?mode=editgroups&group_id=".$group_id." \">".$Language->getText('docman_doc_utils','edit_groups')."</a></b>";
    
    if ($params['help']) {
	echo ' | <b>  '.help_button($params['help'],false,$Language->getText('global','help')).'</b>';
    }
}

function docman_footer($params) {
	site_project_footer($params);
}

function doc_get_title_from_id($docid) {
    $res=db_query("SELECT title FROM doc_data WHERE docid=$docid");
    return db_result($res,0,'title');
}

function doc_get_docgroupname_from_id($doc_group) {
    $res=db_query("SELECT groupname FROM doc_groups WHERE doc_group=$doc_group",1);
    return db_result($res,0,'groupname');
}

?>
