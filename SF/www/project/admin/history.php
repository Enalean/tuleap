<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

project_admin_header(array('title'=>'Project History','group'=>$group_id));
?>

<H3>Project Change Log</H3>
<P>
This log will show who made significant changes to your project and when.
<P>
<?php
echo show_grouphistory($group_id);

project_admin_footer(array());
?>
