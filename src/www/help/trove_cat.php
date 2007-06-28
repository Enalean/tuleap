<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require "pre.php";    
$Language->loadLanguageMsg('help/help');

$res_cat = db_query("SELECT * FROM trove_cat WHERE trove_cat_id=$helpid");
if (db_numrows($res_cat)<1) {
  print $Language->getText('help_trove_cat','no_such_trove_cat');
	exit;
}
$row_cat = db_fetch_array($res_cat);

help_header($Language->getText('help_trove_cat','trove_cat',$row_cat['fullname']));

print '<TABLE width="100%" cellpadding="0" cellspacing="0" border="0">'."\n";
print '<TR><TD>'.$Language->getText('help_trove_cat','full_cat_name').':</TD><TD><B>'.$row_cat['fullname']."</B></TD>\n";
print '<TR><TD>'.$Language->getText('help_trove_cat','short_name').':</TD><TD><B>'.$row_cat['shortname']."</B></TD>\n";
print "</TABLE>\n"; 
print '<P>'.$Language->getText('help_trove_cat','desc').':<BR><I>'.$row_cat['description'].'</I>'."\n";

help_footer();
?>
