<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../people/people_utils.php');

people_header(array('title'=>'Help Wanted System',
		    'help' => 'Post-EditJobs.html'));

if ($group_id) {

	echo '<H3>Project Help Wanted for '. group_getname($group_id) .'</H3>
	<P>
	Here is a list of positions available for this project.
	<P>';

	echo people_show_project_jobs($group_id);
	
} else if ($category_id) {

	echo '<H3>Projects looking for '. people_get_category_name($category_id) .'</H3>
		<P>
		Click job titles for more detailed descriptions.
		<P>';
	echo people_show_category_jobs($category_id);

} else {

    util_get_content('people/browse_projects');
	echo people_show_category_table();

}

people_footer(array());

?>
