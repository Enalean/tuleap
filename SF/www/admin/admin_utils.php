<?php

function site_admin_header($params) {
	GLOBAL $HTML;
	global $feedback;
	$HTML->header($params);
	echo html_feedback_top($feedback);
	echo '<H2>'.$GLOBALS['sys_name'].' Site Admin</H2>
	<P><A HREF="/admin/">Site Admin Home</A>
	<P>';
}

function site_admin_footer($vals=0) {
	GLOBAL $HTML;
	echo html_feedback_bottom($feedback);
	$HTML->footer(array());
}

function show_group_type_box($name='group_type',$checked_val='xzxz') {
	$result=db_query("SELECT * FROM group_type");
	return html_build_select_box ($result,'group_type',$checked_val,false);
}

function show_project_type_box($checked_val='xzxz') {
	$result=db_query("SELECT project_type_id, description FROM project_type");
	return html_build_select_box ($result,'project_type',$checked_val,false);
}
?>