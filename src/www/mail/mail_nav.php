<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

echo "\n\n<TABLE BORDER=0 WIDTH=\"100%\">".
	"\n<TR><TD NOWRAP>";

echo "<A HREF=\"/project/?group_id=$group_id\">".
	"<IMG SRC=\"".util_get_image_theme("ic/ofolder15.png")."\" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp; ".group_getname($group_id)." ".$Language->getText('mail_nav','home_page')."</A><BR>";
echo " &nbsp; &nbsp; <A HREF=\"/mail/?group_id=$group_id\">".
        "<IMG SRC=\"".util_get_image_theme("ic/ofolder15.png")."\" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp; ".$Language->getText('mail_nav','mail_list')."</A><BR>";
if ($is_admin_page) {
        echo " &nbsp; &nbsp; &nbsp; &nbsp; <A HREF=\"/survey/admin/?group_id=$group_id\">".
                "<IMG SRC=\"".util_get_image_theme("ic/ofolder15.png")."\" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp; ".$Language->getText('mail_nav','admin')."</A>";
}
echo "</TD></TR>";
echo "\n</TABLE>\n";

?>
