<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

/*
        Docmentation Manager
        by Quentin Cregan, SourceForge 06/2000
*/

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/project/admin/permissions.php');
require('./doc_utils.php');

if (!$group_id) {
    exit_no_group();
}

$Language->loadLanguageMsg('docman/docman');

$params=array('title'=>$Language->getText('docman_index','title',array(group_getname($group_id))),
              'help'=>'DocumentManager.html',
              'pv'=>$pv);
docman_header($params);

if ($pv) {
    echo "<h2>".$Language->getText('docman_index','header')."</h2>";
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H2>'.$Language->getText('docman_index','header').'</H2>';
    echo "</TD>";
    echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".
        util_get_image_theme("msg.png")."' border='0'>&nbsp;".
        $Language->getText('global','printer_version')."</A> ) </TD>";
    echo "</TR></TABLE>";    

}

//get a list of group numbers that this project owns
$query = "select * "
."from doc_groups "
."where group_id = $group_id "
."order by group_rank, groupname";
$result = db_query($query); 

//otherwise, throw up an error
if (db_numrows($result) < 1) {
    print "<b>".$Language->getText('docman_index','nodoc')."</b><p>";
} else { 
    // Retain only document groupsthe user is authorized to access, or those that contain authorized documents...
    $authorized_user=false;
    if (user_ismember($group_id,'D2') || user_ismember($group_id,'A')) {
        $authorized_user=true;
    }
    while ($row = db_fetch_array($result)) {
        $doc_group=$row['doc_group'];
        $authorized=false;
        $authorized_on_docgroup=false;
        if (($authorized_user)||(permission_is_authorized('DOCGROUP_READ',$doc_group,user_getid(),$group_id))) {
            $authorized=true;
            $authorized_on_docgroup=true;
        } else {
            // Get corresponding documents and check access. 
            // When set, the document permission overwrite document group permission
            $sql2= "SELECT * FROM doc_data WHERE doc_group=".$doc_group;
            $res2=db_query( $sql2 );
            if (db_numrows($res2)>0) {
                while ($row2 = db_fetch_array($res2)) {
                    if (permission_exist('DOCUMENT_READ', $row2['docid'])) {
                        if (permission_is_authorized('DOCUMENT_READ',$row2['docid'],user_getid(),$group_id)) {
                            $authorized=true;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($authorized) {
            // get the groupings and display them with their members.
            $query = "select description, docid, title, doc_group "
                ."from doc_data "
                ."where doc_group = '".$doc_group."' ";
            $query .= " order by rank";
            $subresult = db_query($query); 
            
            if (!(db_numrows($subresult) < 1)) {
                print "<p><b>".$row['groupname']."</b>";
                if ($authorized_user) {
                    if (permission_exist('DOCGROUP_READ',$doc_group)) {
                        if (!$pv) print ' <a href="/docman/admin/editdocgrouppermissions.php?doc_group='.$doc_group.
                            '&group_id='.$group_id.'"><img src="'.util_get_image_theme("ic/lock.png").'" border="0"></a>';
                    }
                }
                print "\n<ul>\n";
                while ($subrow = db_fetch_array($subresult)) {
                    if (permission_exist('DOCUMENT_READ', $subrow['docid'])) {
                       if (!permission_is_authorized('DOCUMENT_READ',$subrow['docid'],user_getid(),$group_id)) {
                            continue;
                        }
                    } else if (!$authorized_on_docgroup) {
                        continue;
                    }

                    // LJ We want the title and the description to
                    // possibly contain HTML but NOT php code
                    print "<li><a href=\"display_doc.php?docid=".$subrow['docid']."&group_id=".
                        $group_id."\" title=\"".$subrow['docid']." - ".strip_tags(util_unconvert_htmlspecialchars($subrow['title']))."\">";
                    print(util_unconvert_htmlspecialchars($subrow['title']));
                    print "</a>\n";
                    if ($authorized_user) {
                        if (permission_exist('DOCUMENT_READ',$subrow['docid'])) {
                            if (!$pv) print ' <a href="/docman/admin/editdocpermissions.php?docid='.$subrow['docid'].
                                '&group_id='.$group_id.'"><img src="'.util_get_image_theme("ic/lock.png").'" border="0"></a>';
                        }
                    }

                    print "<BR><i>".$Language->getText('docman_index','description').":</i> ";
                    print(util_unconvert_htmlspecialchars($subrow['description'])); 
                    
                }
                print "</ul>\n\n";
                
            }
            
            
            $res_package[$row['package_id']]=$row['name'];
            $num_packages++;
        }
    }
 }

docman_footer($params);

?>
