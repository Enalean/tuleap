<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    

$res_cat = db_query("SELECT * FROM trove_cat WHERE trove_cat_id=$trove_cat_id");
if (db_numrows($res_cat)<1) {
	print "No such trove category";
	exit;
}
$row_cat = db_fetch_array($res_cat);

help_header("Trove Category - ".$row_cat['fullname']);

print '<TABLE width="100%" cellpadding="0" cellspacing="0" border="0">'."\n";
print '<TR><TD>Full Category Name:</TD><TD><B>'.$row_cat['fullname']."</B></TD>\n";
print '<TR><TD>Short Name:</TD><TD><B>'.$row_cat['shortname']."</B></TD>\n";
print "</TABLE>\n"; 
print '<P>Description:<BR><I>'.$row_cat['description'].'</I>'."\n";

help_footer();
?>
