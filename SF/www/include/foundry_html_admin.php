<?php

require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

//must be a project admin
session_require(array('group'=>$group_id,'admin_flags'=>'A'));


if ($update) {
	db_query("UPDATE foundry_data SET $field='$freeform_html' WHERE foundry_id='$group_id'");
	echo db_error();
}

project_admin_header(array('title'=>"Project Admin: ".group_getname($group_id),'group'=>$group_id));

if (!$field) {

	?>
	<H3>Choose A Field To Edit</H3>
	<P>
	<A HREF="/foundry/<?php echo $expl_pathinfo[2]; ?>/admin/html/?field=freeform1_html">Top Freeform HTML</A><BR>
	<A HREF="/foundry/<?php echo $expl_pathinfo[2]; ?>/admin/html/?field=freeform2_html">Bottom Freeform HTML</A><BR>
	<A HREF="/foundry/<?php echo $expl_pathinfo[2]; ?>/admin/html/?field=sponsor1_html">Sponsor HTML</A><BR>
	<?php

} else {

	echo '
	<H3>Preview</H3>
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
	<INPUT TYPE="SUBMIT" NAME="preview" VALUE="Preview">

	<INPUT TYPE="SUBMIT" NAME="update" VALUE="Save Changes"> 
	</FORM>';

}

project_admin_footer(array());

?>
