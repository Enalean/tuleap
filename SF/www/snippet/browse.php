<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../snippet/snippet_utils.php');

$Language->loadLanguageMsg('snippet/snippet');

snippet_header(array('title'=>$Language->getText('snippet_browse','s_library'), 
		     'header'=>$Language->getText('snippet_browse','s_browsing'),
		     'help' => 'TheCodeXMainMenu.html#CodeSnippetBrowsing'));

if ($by=='lang') {

	$sql="SELECT user.user_name,snippet.description,snippet.snippet_id,snippet.name ".
		"FROM snippet,user ".
		"WHERE user.user_id=snippet.created_by AND snippet.language='$lang'";

	$sql2="SELECT user.user_name,snippet_package.description,snippet_package.snippet_package_id,snippet_package.name ".
		"FROM snippet_package,user ".
		"WHERE user.user_id=snippet_package.created_by AND snippet_package.language='$lang'";

	echo '<H2>'.$Language->getText('snippet_browse','s_by_lang',snippet_data_get_language_from_id($lang)).'</H2>';

} else if ($by=='cat') {

	$sql="SELECT user.user_name,snippet.description,snippet.snippet_id,snippet.name ".
		"FROM snippet,user ".
		"WHERE user.user_id=snippet.created_by AND snippet.category='$cat'";

	$sql2="SELECT user.user_name,snippet_package.description,snippet_package.snippet_package_id,snippet_package.name ".
		"FROM snippet_package,user ".
		"WHERE user.user_id=snippet_package.created_by AND snippet_package.category='$cat'";

	echo '<H3>'.$Language->getText('snippet_browse','s_cat',snippet_data_get_category_from_id($cat)).'</H3>';

} else {

	exit_error($Language->getText('global','error'),$Language->getText('snippet_browse','bad_url'));

}

$result=db_query($sql);
$rows=db_numrows($result);

$result2=db_query($sql2);
$rows2=db_numrows($result2);

if ((!$result || $rows < 1) && (!$result2 || $rows2 < 1)) {
	echo '<H2>'.$Language->getText('snippet_browse','no_s_found').'</H2>';
} else {

	$title_arr=array();
	$title_arr[]=$Language->getText('snippet_browse','id');
	$title_arr[]=$Language->getText('snippet_browse','title');
	$title_arr[]=$Language->getText('snippet_browse','creator');

	echo html_build_list_table_top ($title_arr);

	/*
		List packages if there are any
	*/
	if ($rows2 > 0) {
		echo '
			<TR><TD COLSPAN="3"><B>'.$Language->getText('snippet_browse','p_of_s').'</B></TD>';
	}
	for ($i=0; $i<$rows2; $i++) {
		echo '
			<TR class="'. util_get_alt_row_color($i) .'"><TD ROWSPAN="2"><A HREF="/snippet/detail.php?type=package&id='.
			db_result($result2,$i,'snippet_package_id').'"><B>'.
			db_result($result2,$i,'snippet_package_id').'</B></A></TD><TD><B>'.
			db_result($result2,$i,'name').'</TD><TD>'.
			'<a href="/users/'.db_result($result2,$i,'user_name').'"><b>'.
			db_result($result2,$i,'user_name').'</b></a></TD></TR>';
		echo '
			<TR class="'. util_get_alt_row_color($i) .'"><TD COLSPAN="2">'.util_make_links(nl2br(db_result($result2,$i,'description'))).'</TD></TR>';
	}


	/*
		List snippets if there are any
	*/

	if ($rows > 0) {
		echo '
			<TR><TD COLSPAN="3"><B>'.$Language->getText('snippet_browse','s').'</B></TD>';
	}
	for ($i=0; $i<$rows; $i++) {
		echo '
			<TR class="'. util_get_alt_row_color($i) .'"><TD ROWSPAN="2"><A HREF="/snippet/detail.php?type=snippet&id='.
			db_result($result,$i,'snippet_id').'"><B>'.
			db_result($result,$i,'snippet_id').'</B></A></TD><TD><B>'.
			db_result($result,$i,'name').'</TD><TD>'.
			'<a href="/users/'.db_result($result,$i,'user_name').'"><b>'.
			db_result($result,$i,'user_name').'</b></a></TD></TR>';
		echo '
			<TR class="'. util_get_alt_row_color($i) .'"><TD COLSPAN="2">'.util_make_links(nl2br(db_result($result,$i,'description'))).'</TD></TR>';
	}

	echo '
		</TABLE>';

}

snippet_footer(array());

?>
