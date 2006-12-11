<?php

require_once('www/project/admin/project_admin_utils.php');

$Language->loadLanguageMsg('include/include');

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));


if ($update) {
	db_query("UPDATE foundry_data SET $field='$freeform_html' WHERE foundry_id='$group_id'");
	echo db_error();
}

project_admin_header(array('title'=>$Language->getText('include_foundry_admin','proj_admin',group_getname($group_id)),'group'=>$group_id));

if (!$field) {

	echo '
	<H3>'.$Language->getText('include_foundry_html_admin','choose_field').'</H3>
	<P>
	<A HREF="/foundry/'.$expl_pathinfo[2].'/admin/html/?field=freeform1_html">'.$Language->getText('include_foundry_html_admin','top_freeform').'</A><BR>
	<A HREF="/foundry/'.$expl_pathinfo[2].'/admin/html/?field=freeform2_html">'.$Language->getText('include_foundry_html_admin','bottom_freeform').'</A><BR>
	<A HREF="/foundry/'.$expl_pathinfo[2].'/admin/html/?field=sponsor1_html">'.$Language->getText('include_foundry_html_admin','sponsor').'</A><BR>
	';

} else {

	echo '
	<H3>'.$Language->getText('include_foundry_html_admin','preview').'</H3>
	<P>
	<TABLE WIDTH=75%><TR><TD>';

	if ($preview) {
		$freeform_html = stripslashes($freeform_html);
		echo $freeform_html;
	} else {

		$freeform_html = stripslashes(db_result(db_query("SELECT $field FROM foundry_data WHERE foundry_id='$group_id'"),0,"$field"));
		echo $freeform_html;
	}

	echo '</TD></TR></TABLE>
	<P>
	<FORM ACTION="/foundry/'.$expl_pathinfo[2].'/admin/html/" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="field" VALUE="'. $field .'">
	<TEXTAREA NAME="freeform_html" ROWS="25" COLS="70" WRAP="SOFT">'. htmlspecialchars($freeform_html) .'</TEXTAREA>
	<P>
	<INPUT TYPE="SUBMIT" NAME="preview" VALUE="'.$Language->getText('include_foundry_html_admin','preview').'">

	<INPUT TYPE="SUBMIT" NAME="update" VALUE="'.$Language->getText('include_foundry_html_admin','save_changes').'"> 
	</FORM>';

}

project_admin_footer(array());

?>
