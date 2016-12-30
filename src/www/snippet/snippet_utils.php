<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015. All Rights Reserved.
// http://sourceforge.net
//
// 

/*
	Code Snippet System
	By Tim Perdue, Sourceforge, Jan 2000
*/

$csrf = new CSRFSynchronizerToken('/snippet/');

require_once('www/snippet/snippet_data.php');


function snippet_header($params) {
	global $is_snippet_page,$HTML,$feedback,$Language;

        if (ForgeConfig::get('sys_use_snippet') !== 'force') {
            exit_permission_denied();
        }

	// LJ used so the search box will add the necessary element to the pop-up box
	// Codendi Specific
	$is_snippet_page=1;


	$HTML->header($params);
	/*
		Show horizontal links
	*/
	echo '<H2>' . $params['title'] . '</H2>';
	echo '<P><B>';
	echo '<A HREF="/snippet/">'.$Language->getText('snippet_utils','browse').'</A>
		 | <A HREF="/snippet/submit.php">'.$Language->getText('snippet_utils','create_s').'</A>
		 | <A HREF="/snippet/package.php">'.$Language->getText('snippet_utils','create_p').'</A></B>';
	if (isset($params['help']) && $params['help']) {
	    echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
	}
	echo '<P>';
	html_feedback_top($feedback);
}

function snippet_footer($params) {
	GLOBAL $HTML;
	global $feedback;
	html_feedback_bottom($feedback);
	$HTML->footer($params);
}

function snippet_show_package_snippets($version) {
  global $Language;

        $version = (int)$version;
	//show the latest version
	$sql="SELECT snippet.snippet_id, snippet_package_item.snippet_version_id, snippet_version.version,snippet.name,user.user_name, snippet_version.filesize ".
		"FROM snippet,snippet_version,snippet_package_item,user ".
		"WHERE snippet.snippet_id=snippet_version.snippet_id ".
		"AND user.user_id=snippet_version.submitted_by ".
		"AND snippet_version.snippet_version_id=snippet_package_item.snippet_version_id ".
		"AND snippet_package_item.snippet_package_version_id='". db_ei($version) ."'";

	$result=db_query($sql);
	$rows=db_numrows($result);
	echo '
	<P>
	<H3>'.$Language->getText('snippet_add_snippet_to_package','s_in_p').'</H3>
	<P>';

	$title_arr=array();
	$title_arr[]=$Language->getText('snippet_utils','version_id');
	$title_arr[]=$Language->getText('snippet_details','s_version');
	$title_arr[]=$Language->getText('snippet_browse','title');
	$title_arr[]=$Language->getText('snippet_details','author');

	echo html_build_list_table_top ($title_arr,$links_arr);

	if (!$result || $rows < 1) {
		echo db_error();
		echo '
			<TR><TD COLSPAN="4"><H3>'.$Language->getText('snippet_add_snippet_to_package','no_s_in_p').'</H3></TD></TR>';
	} else {

		//get the newest version, so we can display it's code
		$newest_version=db_result($result,0,'snippet_version_id');

		for ($i=0; $i<$rows; $i++) {
			echo '
			<TR class="'. util_get_alt_row_color($i) .'">
                            <TD><A HREF="/snippet/detail.php?type=snippet&id='.db_result($result,$i,'snippet_id').'"><b><center>'.
				db_result($result,$i,'snippet_version_id').'</center></b></A></TD>
                            <TD>';
            echo '<A HREF="/snippet/download.php?type=snippet&id='.
				db_result($result,$i,'snippet_version_id').'"><b><center>'.
				db_result($result,$i,'version').'</b></A>';
            // For uploaded files, the user can choose between view or display the code snippet
            if (db_result($result, $i, 'filesize') != 0) {
                // View link : the file is forced to be displayed as a text
                echo '&nbsp;<a href="/snippet/download.php?mode=view&type=snippet&id='.db_result($result,$i,'snippet_version_id').'">';
                echo '<img src="'.util_get_image_theme("ic/view.png").'" border="0" alt="'.$Language->getText('snippet_details','view').'" title="'.$Language->getText('snippet_details','view').'"></a>';
                // Download link : the file is forced to be downloaded
                echo '&nbsp;<a href="/snippet/download.php?mode=download&type=snippet&id='.db_result($result,$i,'snippet_version_id').'">';
                echo '<img src="'.util_get_image_theme("ic/download.png").'" border="0" alt="'.$Language->getText('snippet_details','download').'" title="'.$Language->getText('snippet_details','download').'"></a>';
            }
            $user = UserManager::instance()->getUserByUserName(db_result($result,$i,'user_name'));
            echo '</center></TD>
                             <TD>'.db_result($result,$i,'name').'</TD><TD>'.
				UserHelper::instance()->getLinkOnUser($user).'</TD></TR>';
		}
	}
	echo '</TABLE>';

}

function snippet_show_package_details($id) {
  global $Language;

        $id = (int)$id;

	$sql="SELECT * FROM snippet_package WHERE snippet_package_id='". db_ei($id) ."'";
	$result=db_query($sql);

	echo '
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<H2>'. db_result($result,0,'name').'</H2>
	</TD></TR>

	<TR>
		<TD><B>'.$Language->getText('snippet_package','category').'</B><BR>
		'.snippet_data_get_category_from_id(db_result($result,0,'category')).'
		</TD>

		<TD><B>'.$Language->getText('snippet_package','language').'</B><BR>
		'.snippet_data_get_language_from_id(db_result($result,0,'language')).'
		</TD>
	</TR>

	<TR><TD COLSPAN="2">&nbsp;<BR><B>'.$Language->getText('snippet_package','description').'</B><BR>
	'. util_make_links(nl2br(db_result($result,0,'description'))).'
	</TD></TR>

	</TABLE>';

}

function snippet_show_snippet_details($id) {
  global $Language;

        $id = (int)$id;

	$sql="SELECT * FROM snippet WHERE snippet_id='". db_ei($id) ."'";
	$result=db_query($sql);

	echo '
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<H2>'. db_result($result,0,'name').'</H2>
	</TD></TR>

	<TR><TD><B>'.$Language->getText('snippet_utils','type').'</B><BR>
		'.snippet_data_get_type_from_id(db_result($result,0,'type')).'</TD>
	<TD><B>'.$Language->getText('snippet_package','category').'</B><BR>
		'.snippet_data_get_category_from_id(db_result($result,0,'category')).'
	</TD></TR>

	<TR><TD><B>'.$Language->getText('snippet_utils','license').'</B><BR>
		'.snippet_data_get_license_from_id(db_result($result,0,'license')).'</TD>
	<TD><B>'.$Language->getText('snippet_package','language').'</B><BR>
		'.snippet_data_get_language_from_id(db_result($result,0,'language')).'
	</TD></TR>

	<TR><TD COLSPAN="2">&nbsp;<BR>
	<B>'.$Language->getText('snippet_package','description').'</B><BR>
	'. util_make_links(nl2br(db_result($result,0,'description'))).'
	</TD></TR>

	</TABLE>';
}

function snippet_edit_package_details($id) {
  global $Language, $csrf;

        $id = (int)$id;


	$sql="SELECT * FROM snippet_package WHERE snippet_package_id='". db_ei($id) ."'";
	$result=db_query($sql);

	echo '
	<FORM ACTION="" METHOD="POST" enctype="multipart/form-data">'.
        $csrf->fetchHTMLInput() .'
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
	<B>'.$Language->getText('snippet_browse','title').'</B><BR>
        <INPUT TYPE="TEXT" NAME="snippet_name" SIZE="45" MAXLENGTH="60" VALUE="'.db_result($result,0,'name').'">
	</TD></TR>

	<TR>
		<TD><B>'.$Language->getText('snippet_package','category').'</B><BR>
		'.html_build_select_box(snippet_data_get_all_categories() ,'snippet_category',db_result($result,0,'category'),false).'
		</TD>

		<TD><B>'.$Language->getText('snippet_package','language').'</B><BR>
		'.html_build_select_box(snippet_data_get_all_languages() ,"snippet_language",db_result($result,0,'language'),false).'
		</TD>
	</TR>

	<TR><TD COLSPAN="2">&nbsp;<BR><B>'.$Language->getText('snippet_package','description').'</B><BR>
	    <TEXTAREA NAME="snippet_description" ROWS="5" COLS="45" WRAP="SOFT">'.db_result($result,0,'description').'</TEXTAREA>
	</TD></TR>
	<TR><TD COLSPAN="2">
		<B>'.$Language->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
		<BR><BR>
		<INPUT CLASS="btn btn-primary" TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	</TD></TR>
	</TABLE>
	</FORM>
        <HR>
';

}


function snippet_edit_snippet_details($id) {
  global $Language, $csrf;

        $id = (int)$id;


	$sql="SELECT * FROM snippet WHERE snippet_id='". db_ei($id) ."'";
	$result=db_query($sql);

	echo '
	<FORM ACTION="" METHOD="POST" enctype="multipart/form-data">'.
        $csrf->fetchHTMLInput() .'
	<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	<P>
	<TABLE WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2">

	<TR><TD COLSPAN="2">
        <B>'.$Language->getText('snippet_browse','title').'</B>&nbsp;
	<INPUT TYPE="TEXT" NAME="snippet_name" SIZE="45" MAXLENGTH="60" VALUE="'.db_result($result,0,'name').'">
	</TD></TR>

	<TR><TD><B>'.$Language->getText('snippet_utils','type').'</B><BR>
		'.html_build_select_box(snippet_data_get_all_types() ,'snippet_type',db_result($result,0,'type'),false).'
        </TD><TD><B>'.$Language->getText('snippet_package','category').'</B><BR>
		'.html_build_select_box(snippet_data_get_all_categories() ,'snippet_category',db_result($result,0,'category'),false).'
	</TD></TR>

	<TR><TD><B>'.$Language->getText('snippet_utils','license').'</B><BR>
		'.html_build_select_box(snippet_data_get_all_licenses() ,'snippet_license',db_result($result,0,'license'),false).'
        </TD><TD><B>'.$Language->getText('snippet_package','language').'</B><BR>
		'.html_build_select_box(snippet_data_get_all_languages() ,"snippet_language",db_result($result,0,'language'),false).'
	</TD></TR>

	<TR><TD COLSPAN="2">&nbsp;<BR>
	<B>'.$Language->getText('snippet_package','description').'</B><BR>
	    <TEXTAREA NAME="snippet_description" ROWS="5" COLS="45" WRAP="SOFT">'.db_result($result,0,'description').'</TEXTAREA>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<B>'.$Language->getText('snippet_add_snippet_to_package','all_info_complete').'</B>
		<BR><BR>
		<INPUT CLASS="btn btn-primary" type="submit" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	</TD></TR>
	</TABLE>
	</FORM>
        <HR>
';
}
