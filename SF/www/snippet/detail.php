<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../snippet/snippet_utils.php');

$Language->loadLanguageMsg('snippet/snippet');

/*

	Show a detail page for either a snippet or a package
	or a specific version of a package

*/
if ($type=='snippet') {



    // Snippet was updated?
    if (isset($post_changes) && $post_changes) {
        // The author or site admin have updated the snippet
        if (snippet_data_can_modify_snippet($id)) {
            if ($snippet_license==100) {
                // No license!
		$feedback .= ' '.$Language->getText('snippet_details','select_license').' ';
            } else if ($snippet_category==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_category').' ';
            } else if ($snippet_type==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_type').' ';
            } else if ($snippet_language==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_lang').' ';
            } else {
                $sql="UPDATE snippet SET category=$snippet_category, type=$snippet_type, license=$snippet_license, language=$snippet_language, name='".
                    htmlspecialchars($snippet_name)."', description='".
                    htmlspecialchars($snippet_description)."' WHERE snippet_id=$id";
                $result=db_query($sql);
                if (!$result) {
                    $feedback .= ' '.$Language->getText('snippet_details','upd_fail').' ';
                    echo db_error();
                } else {
                    $feedback .= ' '.$Language->getText('snippet_details','upd_success').' ';
                }
            }
        }
    }
	/*


		View a snippet and show its versions
		Expand and show the code for the latest version


	*/

	snippet_header(array('title'=>$Language->getText('snippet_browse','s_library')));

        // Only the snippet author(s) or site admin may edit snippet details
        if (snippet_data_can_modify_snippet($id)) {
            snippet_edit_snippet_details($id);
        } else {
            snippet_show_snippet_details($id);
        }

	/*
		Get all the versions of this snippet
	*/
	$sql="SELECT user.user_name,snippet_version.snippet_version_id,snippet_version.version,snippet_version.date,snippet_version.changes, snippet_version.filesize ".
		"FROM snippet_version,user ".
		"WHERE user.user_id=snippet_version.submitted_by AND snippet_id='$id' ".
		"ORDER BY snippet_version.snippet_version_id DESC";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '<H3>'.$Language->getText('snippet_details','no_v_found').'</H3>';
	} else {
		echo '
		<H3>'.$Language->getText('snippet_details','versions_of_s').'</H3>
		<P>';
		$title_arr=array();
		$title_arr[]=$Language->getText('snippet_utils','version_id');
		$title_arr[]=$Language->getText('snippet_details','s_version');
		$title_arr[]=$Language->getText('snippet_details','rel_notes');
		$title_arr[]=$Language->getText('snippet_details','posted_on');
		$title_arr[]=$Language->getText('snippet_details','author');
		$title_arr[]=$Language->getText('snippet_details','delete');

		echo html_build_list_table_top ($title_arr);

		/*
			get the newest version of this snippet, so we can display its code
		*/
		$newest_version=db_result($result,0,'snippet_version_id');

		for ($i=0; $i<$rows; $i++) {
			echo '<TR class="'. html_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'snippet_version_id').'</TD>';
            echo '<TD><center>';
            // Auto link : the browser manage how to open/save the file
            echo '<A HREF="/snippet/download.php?type=snippet&id='.db_result($result,$i,'snippet_version_id').'">';
            echo '<B>'.db_result($result,$i,'version').'</B></A>';
            // For uploaded files, the user can choose between view or display the code snippet
            if (db_result($result, $i, 'filesize') != 0) {
                // View link : the file is forced to be displayed as a text
                echo '&nbsp;<a href="/snippet/download.php?mode=view&type=snippet&id='.db_result($result,$i,'snippet_version_id').'">';
                echo '<img src="'.util_get_image_theme("ic/view.png").'" border="0" alt="'.$Language->getText('snippet_details','view').'" title="'.$Language->getText('snippet_details','view').'"></a>';
                // Download link : the file is forced to be downloaded
                echo '&nbsp;<a href="/snippet/download.php?mode=download&type=snippet&id='.db_result($result,$i,'snippet_version_id').'">';
                echo '<img src="'.util_get_image_theme("ic/download.png").'" border="0" alt="'.$Language->getText('snippet_details','download').'" title="'.$Language->getText('snippet_details','download').'"></a>';
            }
            echo '</center>
                </TD><TD>'. 
			        nl2br(db_result($result,$i,'changes')).'</TD><TD align="center">'.
			        format_date($sys_datefmt,db_result($result,$i,'date')).'</TD><TD>'.
				'<a href="/users/'.db_result($result,$i,'user_name').'"><b>'.
				db_result($result,$i,'user_name').'</b></a></TD><TD ALIGN="center"><A HREF="/snippet/delete.php?type=snippet&snippet_version_id='.
				db_result($result,$i,'snippet_version_id').
				'"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD></TR>';

		}
		echo '</TABLE>';

		echo '
		<P>
		'.$Language->getText('snippet_details','download_s').'</p>';
		echo '<p>'.$Language->getText('snippet_details','submit_s',"/snippet/addversion.php?type=snippet&id=$id").'</p>';

	}
	/*
		show the latest version of this snippet's code
	*/
	$result=db_query("SELECT code,version,filename,filesize FROM snippet_version WHERE snippet_version_id='$newest_version'");	

	echo '
		<P>
		<HR>
		<P>
		<H2>'.$Language->getText('snippet_details','latest_s_v',db_result($result,0,'version')).'</H2>';

	if (db_result($result,0,'filename')) {

	    echo '<P> '.db_result($result,0,'filename').
		' ('.sprintf('%d', db_result($result,0,'filesize')/1024).' KB)'.
		'&nbsp;&nbsp;<a href="/snippet/download.php?mode=view&type=snippet&id='.$newest_version.'"><b>'.$Language->getText('snippet_details','view_s').'</b></a>';
        echo '<a href="/snippet/download.php?mode=download&type=snippet&id='.$newest_version.'"><b>'.$Language->getText('snippet_details','down_s').'</b></a>';

	} else {
	    echo '<P>
		<PRE><FONT SIZE="-1">'. db_result($result,0,'code') .'
		</FONT></PRE>
		<P>';
	}

	snippet_footer(array());

} else if ($type=='package') {


    // Snippet package was updated?
    if (isset($post_changes) && $post_changes) {
        // The author or site admin have updated the snippet
        if (snippet_data_can_modify_snippet_package($id)) {
            if ($snippet_category==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_category').' ';
            } else if ($snippet_language==100) {
		$feedback .= ' '.$Language->getText('snippet_details','select_lang').' ';
            } else {
                $sql="UPDATE snippet_package SET category=$snippet_category, language=$snippet_language, name='".
                    htmlspecialchars($snippet_name)."', description='".
                    htmlspecialchars($snippet_description)."' WHERE snippet_package_id=$id";
                $result=db_query($sql);
                if (!$result) {
                    $feedback .= ' '.$Language->getText('snippet_details','p_upd_fail').' ';
                    echo db_error();
                } else {
                    $feedback .= ' '.$Language->getText('snippet_details','p_upd_success').' ';
                }
            }
        }
    }

	/*


		View a package and show its versions
		Expand and show the snippets for the latest version


	*/

	snippet_header(array('title'=>$Language->getText('snippet_browse','s_library')));


        // Only the snippet package author(s) or site admin may edit snippet details
        if (snippet_data_can_modify_snippet_package($id)) {
            snippet_edit_package_details($id);
        } else {
            snippet_show_package_details($id);
        }


	/*
		Get all the versions of this package
	*/
	$sql="SELECT user.user_name,snippet_package_version.snippet_package_version_id,".
		"snippet_package_version.version,snippet_package_version.date ".
		"FROM snippet_package_version,user ".
		"WHERE user.user_id=snippet_package_version.submitted_by AND snippet_package_id='$id' ".
		"ORDER BY snippet_package_version.snippet_package_version_id DESC";

	$result=db_query($sql);
	$rows=db_numrows($result);
	if (!$result || $rows < 1) {
		echo '<H3>'.$Language->getText('snippet_details','no_v_found').'</H3>';
	} else {
		echo '
		<H3>'.$Language->getText('snippet_details','versions_of_p').'</H3>
		<P>';
		$title_arr=array();
		$title_arr[]=$Language->getText('snippet_details','p_version');
		$title_arr[]=$Language->getText('snippet_details','rel_notes');
		$title_arr[]=$Language->getText('snippet_details','posted_on');
		$title_arr[]=$Language->getText('snippet_details','author');
		$title_arr[]=$Language->getText('snippet_details','edit');
		$title_arr[]=$Language->getText('snippet_details','delete');

		echo html_build_list_table_top ($title_arr);

		/*
			determine the newest version of this package, 
			so we can display the snippets that it contains
		*/
		$newest_version=db_result($result,0,'snippet_package_version_id');

		for ($i=0; $i<$rows; $i++) {

		    $changes = db_result($result,$i,'changes');
		    if ($changes) {
			$changes_output = '<td>'.nl2br($changes).'</td>';
		    } else {
			$changes_output = '<td align="center">-</td>';
		    }

			echo '
			<TR class="'. html_get_alt_row_color($i) .'"><TD><A HREF="/snippet/detail.php?type=packagever&id='.$id.'&vid='.
				db_result($result,$i,'snippet_package_version_id').'"><B><center>'.
				db_result($result,$i,'version').'</center></B></A></TD>'.
				$changes_output.'<td align="center">'.
			        format_date($sys_datefmt,db_result($result,$i,'date')).'</TD><TD align="center">'.
				'<a href="/users/'.db_result($result,$i,'user_name').'"><b>'.
				db_result($result,$i,'user_name').
				'</b></a></TD><TD ALIGN="center">'.
			        '<A HREF="/snippet/add_snippet_to_package.php?snippet_package_version_id='.
				db_result($result,$i,'snippet_package_version_id').
				'"><IMG SRC="'.util_get_image_theme("ic/notes.png").'" BORDER="0"></A></TD><TD ALIGN="center">'.
				'<A HREF="/snippet/delete.php?type=package&snippet_package_version_id='.
				db_result($result,$i,'snippet_package_version_id').
				'"><IMG SRC="'.util_get_image_theme("ic/trash.png").'" BORDER="0"></A></TD></TR>';
		}
		echo '</TABLE>';

		
		echo '
                            <P>'.$Language->getText('snippet_details','submit_p',"/snippet/addversion.php?type=package&id=$id").'</p>';
	}

	/*
		show the latest version of the package
		and its snippets
	*/

	echo '
		<P>
		<HR>
		<P>
		<H2>'.$Language->getText('snippet_details','latest_p_v',db_result($result,0,'version')).'</H2>
		<P>
		<P>';
	snippet_show_package_snippets($newest_version);

	echo '
		<P>
		'.$Language->getText('snippet_details','download_s').'</p>';

	snippet_footer(array());

} else if ($type=='packagever') {
	/*
		Show a specific version of a package and its specific snippet versions
	*/
	
	snippet_header(array('title'=>$Language->getText('snippet_browse','s_library')));

	snippet_show_package_details($id);

	snippet_show_package_snippets($vid);

	snippet_footer(array());

} else {

	exit_error($Language->getText('global','error'),$Language->getText('snippet_delete','url_mangled'));

}

?>
