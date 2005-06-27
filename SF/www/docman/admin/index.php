<?php
  //
  // SourceForge: Breaking Down the Barriers to Open Source Development
  // Copyright 1999-2000 (c) The SourceForge Crew
  // http://sourceforge.net
  //
  //
  // CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
  // Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
  // http://codex.xerox.com
  //
  // $Id$
  //
  //	Originally written by Quentin Cregan, SourceForge 06/2000
  //	Modified by Laurent Julliard 2001-2004, CodeX Team, Xerox

require_once('pre.php');
require_once('../doc_utils.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('www/project/admin/permissions.php');

if (!($group_id)) {
    exit_no_group();
 }

$Language->loadLanguageMsg('docman/docman');

if (!(user_ismember($group_id,"D2"))) {
    $feedback .= $Language->getText('docman_admin_index','error_perm');
    exit_permission_denied();
 }

function main_page($group_id) {
    global $Language;
    docman_header_admin(array('title'=>$Language->getText('docman_admin_index','title'),
			      'help'=>'DocumentAdministration.html'));
    echo '<h2>'.$Language->getText('docman_admin_index','header_doc_mgt').'</h2>';
    display_docs($group_id);
    docman_footer($params);	
}


function group_main_page($group_id) {
    global $Language;
    docman_header_admin(array('title'=>$Language->getText('docman_admin_index','title_group_mgt'),
			      'help'=>'DocumentAdministration.html#DocumentGroupManagement'));
    echo '<h2>'.$Language->getText('docman_admin_index','header_group_mgt').'</h2>
	    <h3>'.$Language->getText('docman_admin_index','create_doc_group').'</h3>
	    <form name="addgroup" action="index.php?mode=groupadd&group_id='.$group_id.'" method="POST">
	    <table>
	        <tr><th>'.$Language->getText('docman_doc_utils','group_name').':</th>  <td><input type="text" name="groupname" size="32"></td></tr>
	        <tr><th>'.$Language->getText('docman_doc_utils','rank').':</th>  <td><input type="text" name="group_rank" size="4"></td></tr>
                <tr><td><input type="submit" value="'.$Language->getText('global','btn_create').'"></td></tr></table>	
	    </form>	
	<h3>'.$Language->getText('docman_admin_index','doc_group_list').'</h3>	';
    display_groups($group_id);
}


//begin to seek out what this page has been called to do.
if ($func=='update_permissions') {
    list ($return_code, $feedback) = permission_process_selection_form($_POST['group_id'], $_POST['permission_type'], $_POST['object_id'], $_POST['ugroups']);
    if (!$return_code) exit_error('Error',$Language->getText('docman_admin_index','error_updating_perm').'<p>'.$feedback);
 }

if ($_POST['reset']) {
    // Must reset access rights to defaults
    if (permission_clear_all($group_id, $_POST['permission_type'], $_POST['object_id'])) {
        $feedback=$Language->getText('docman_admin_index','perm_reset');
    } else {
        $feedback=$Language->getText('docman_admin_index','error_resetting perm');
    }
 }

if (strstr($mode,"docedit")) {
    $query = "select * from doc_data,doc_groups "
	."where docid='$docid' "
	."and doc_groups.doc_group = doc_data.doc_group "
	."and doc_groups.group_id = '$group_id'";
    $result = db_query($query);
    $row = db_fetch_array($result);
    
    docman_header_admin(array('title'=>$Language->getText('docman_admin_index','title_edit'),
                              'help'=>'DocumentAdministration.html#DocumentUpdate'));
    
    echo '
	
<form name="editdata" action="index.php?mode=docdoedit&group_id='.$group_id.'" method="POST"  enctype="multipart/form-data">
  <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">
  <table border="0" width="75%">
  <tr>
      <th>'.$Language->getText('docman_new','doc_title').':</th>
      <td><input type="text" name="title" size="60" maxlength="255" value="'.$row['title'].'"></td>
  </tr>
  <tr></tr>
  <tr>
      <th>'.$Language->getText('docman_new','doc_desc').':</th>
      <td><textarea cols="60" rows="4"  wrap="virtual" name="description">'.$row['description'].'</textarea></td>
  </tr>
  <tr>
       <th> <input type="checkbox" name="upload_instead" value="1"> <B>'.$Language->getText('docman_new','doc_upload').':</B></th>
       <td> <input type="file" name="uploaded_data" size="50">
         <br><span class="smaller"><i>'.$Language->getText('docman_new','max_size_msg',array(formatByteToMb($sys_max_size_upload))).'
         </i></span>
	</td>
   </tr>';
    // Display the content only for HTML and text documents that were not uploaded but copy/pasted
    if ( ( ($row['filetype'] == 'text/html')||($row['filetype'] == 'text/plain') ) && ($row['filesize']==0) ){
        echo '<tr>
	<th valign="top"><br><br>'.$Language->getText('docman_admin_index','doc_edit').':</th>
	<td><textarea cols="60" rows="20" wrap="virtual" name="data">';
        echo $row['data'];
        echo '</textarea></td>
	</tr>';
    }

    echo '<tr>
	<th>'.$Language->getText('docman_new','doc_group').':</th>
        	<td>';

    display_groups_option($group_id,$row['doc_group']);
    
    echo '</td>
    </tr>
    <tr>
        <th>'.$Language->getText('docman_doc_utils','rank_in_group').':</th>
        <td><input type="text" size="3" maxlength="3" name="rank" value="'.$row['rank'].'"/>';
    echo '</td>
   </tr>
  </table>

  <input type="hidden" name="docid" value="'.$row['docid'].'">
  <input type="submit" value="'.$Language->getText('global','btn_submit').'">

</form>';

    docman_footer($params);

 } elseif (strstr($mode,"groupdelete")) {
     $query = "select docid "
         ."from doc_data "
         ."where doc_group = ".$doc_group."";
     $result = db_query($query);
     if (db_numrows($result) < 1) {
         $query = "delete from doc_groups "
             ."where doc_group = '$doc_group' "
             ."and group_id = $group_id";
         db_query($query);
         $feedback .= $Language->getText('docman_admin_index','msg_group_del',array($doc_group));
         group_main_page($group_id);	
     } else {	
         db_query($query);
         $feedback .= $Language->getText('docman_admin_index','msg_group_del_fail');
         group_main_page($group_id);
     }
    
 } elseif (strstr($mode,"groupedit")) {
     docman_header_admin(array('title'=>$Language->getText('docman_admin_index','title_group_edit'),
                               'help'=>'DocumentAdministration.html#DocumentGroupManagement'));
     $query = "select * "
         ."from doc_groups "
         ."where doc_group = '$doc_group' "
         ."and group_id=$group_id";
     $result = db_query($query);
     $row = db_fetch_array($result);
     echo '
			<h2>'.$Language->getText('docman_admin_index','header_group_edit').'</h2>

			<form name="editgroup" action="index.php?mode=groupdoedit&group_id='.$group_id.'" method="POST">
			<table>
			<tr><th>'.$Language->getText('docman_doc_utils','group_name').':</th>  <td><input type="text" size="55" name="groupname" value="'.$row['groupname'].'"></td></tr>
                        <tr><th>'.$Language->getText('docman_doc_utils','rank').':</th>  <td><input type="text" name="group_rank" size="4" maxlength="4" value="'.$row['group_rank'].'"></td></tr>
			<input type="hidden" name="doc_group" value="'.$row['doc_group'].'">
			<tr><td> <input type="submit" name="submit" value="'.$Language->getText('global','btn_submit').'"></td></tr></table>	
			</form>	
			';
     docman_footer($params);
    
 } elseif (strstr($mode,"groupdoedit")) {
     $query = "update doc_groups "
         ."set groupname='".htmlspecialchars($groupname)."', "
         ."group_rank='".$group_rank."' "
         ."where doc_group='$doc_group' "
         ."and group_id = '$group_id'";
     db_query($query);
     $feedback .= $Language->getText('docman_admin_index','feedback_group_updated');
     group_main_page($group_id);
    
 } elseif (strstr($mode,"docdoedit")) {
    
     // Check a number of things: group_id is correct, title and
     // description are non empty
     if (!$doc_group || $doc_group ==100) {
         //cannot add a doc unless an appropriate group is provided
         exit_error($Language->getText('global','error'),
                    $Language->getText('docman_admin_index','error_nodocgroup'));
     }
    
     if (!$title || !$description) { 
         exit_missing_param();
     }
    
     //Page security - checks someone isnt updating a doc
     //that isnt theirs.

     $query = "select dd.docid "
         ."from doc_data dd, doc_groups dg "
         ."where dd.doc_group = dg.doc_group "
         ."and dg.group_id = ".$group_id." "
         ."and dd.docid = '".$docid."'"; 
		
     $result = db_query($query);
	
     if (db_numrows($result) == 1) {

         // Upload the document if needed
        if ($upload_instead) {
            $fileName = $_FILES['uploaded_data']['name'];
            $tmpName  = $_FILES['uploaded_data']['tmp_name'];
            $fileSize = $_FILES['uploaded_data']['size'];
            $fileType = get_mime_content_type($_FILES['uploaded_data']['tmp_name']);

            //echo " filesize=".$fileSize;
            $fp   = fopen($tmpName, 'r');
            $data = addslashes(fread($fp, filesize($tmpName)));
            fclose($fp);
            //echo "strlen(data) =".strlen($data);	  
            if (($fileSize <= 0 ) || ($fileSize >= $sys_max_size_upload)) {
                //too big or small
                exit_error($Language->getText('global','error'),
                           $Language->getText('docman_new','error_size',array($sys_max_size_upload)));
            }
            else {
                //size is fine
                $feedback .= $Language->getText('docman_admin_index','feedback_doc_uploaded');
            }
        }

         if ($upload_instead) {
             // Upload file
             $query = "update doc_data "
                 ."set title = '".htmlspecialchars($title)."', "
                 ."data = '".$data."', "
                 ."updatedate = '".time()."', "
                 ."doc_group = '".$doc_group."', "
                 ."description = '".htmlspecialchars($description)."', "
                 ."filename = '".$fileName."', "
                 ."filesize = '".$fileSize."', "
                 ."filetype = '".$fileType."', "
                 ."rank = '".$rank."' "
                 ."where docid = '".$docid."'"; 
         } else {
             if ( strlen($data) > 0 ) {
                 // Copy/paste data
                 $query = "update doc_data "
                     ."set title = '".htmlspecialchars($title)."', "
                     ."data = '".htmlspecialchars($data)."', "
                     ."updatedate = '".time()."', "
                     ."doc_group = '".$doc_group."', "
                     ."description = '".htmlspecialchars($description)."', "
                     ."filename = '', "
                     ."filesize = '0', "
                     ."filetype = 'text/html', "
                     ."rank = '".$rank."' "
                     ."where docid = '".$docid."'"; 
             } else {
                 // No new document - Just update the associated data
                 $query = "update doc_data "
                     ."set title = '".htmlspecialchars($title)."', "
                     ."updatedate = '".time()."', "
                     ."doc_group = '".$doc_group."', "
                     ."description = '".htmlspecialchars($description)."', "
                     ."rank = '".$rank."' "
                     ."where docid = '".$docid."'"; 
             }
         }
    		
         $res_insert = db_query($query);
         if (db_affected_rows($res_insert) < 1) {
             exit_error($Language->getText('global','error'),
                        $Language->getText('docman_new','error_dbupdate', array(db_error())));
         }
    
         $feedback .= $Language->getText('docman_admin_index','feedback_doc_updated');
         main_page($group_id);

     } else {
         exit_error($Language->getText('global','error'),
                    $Language->getText('docman_admin_index','error_nodoc'));
     }

 } elseif (strstr($mode,"groupadd")) {
     $query = "insert into doc_groups(groupname,group_id,group_rank) " 
         ."values ('"
         .htmlspecialchars($groupname)."',"
         ."'$group_id',"
         ."'$group_rank')";
		
     db_query($query);
     $feedback .= $Language->getText('docman_admin_index','feedback_group_added');
     group_main_page($group_id);
	
 } elseif (strstr($mode,"editgroups")) {
     group_main_page($group_id);

 } elseif (strstr($mode,"docdelete")) {
     // Get title
     $query = "SELECT title FROM doc_data WHERE docid=$docid";
     $result=db_query($query);
     $row = db_fetch_array($result);
     $title=$row['title'];

     $query = "DELETE FROM doc_data WHERE docid=$docid";
     $result=db_query($query);
     if (!$result) {
         $feedback .= " ".$Language->getText('docman_admin_index','error_deleting_doc');
         echo db_error();
     } else {
         $feedback .= " ".$Language->getText('docman_admin_index','doc_deleted');
         // Log in project history
         group_add_history('doc_deleted',$title,$group_id,array($title));
     }

     main_page($group_id);
    
 } else {
    main_page($group_id);
 } //end else

?>
