<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$
require($DOCUMENT_ROOT.'/snippet/snippet_utils.php');

function snippet_mainpage() {
	?>
    <? include(util_get_content('snippet/homepage')); ?>
	<H3>Browse Snippets</H3>
	<P>
	You can browse the snippet library quickly by language or category. You can also search code snippets by keywords using the Search box in the left menu pane.
	<BR>
             (Counts last updated <?php echo date("H:i:s l, F dS, Y") ?>)
	<P>
	<TABLE WIDTH="100%" BORDER="0">
	<TR><TD>

	</TD></TR>

	<TR valign="top"><TD>
	<B>Browse by Language:</B>
	<P>
	<?php

         // List is sorted in alphabetical order
         $sql="SELECT * FROM snippet_language WHERE language_id!=100 ORDER BY language_name";// We don't want 'None' to appear
         $result = db_query ($sql);
         while ($language_array = db_fetch_array($result)) {
             $sql2="SELECT count(*) FROM snippet WHERE language=".$language_array["language_id"];
             $result2 = db_query ($sql2);
             $sql3="SELECT count(*) FROM snippet_package WHERE language=".$language_array["language_id"];
             $result3 = db_query ($sql3);
             $total=(db_result($result2,0,0)+db_result($result3,0,0));
             echo '
		<LI><A HREF="/snippet/browse.php?by=lang&lang='.$language_array["language_id"].'">'.$language_array["language_name"].'</A> ('.$total.')<BR>';
         }


	?>
	</TD>
	<TD>
	<B>Browse by Category:</B>
	<P>
	<?php


         // List is sorted in alphabetical order
         $sql="SELECT * FROM snippet_category WHERE category_id!=100 ORDER BY category_name";// We don't want 'None' to appear
         $result = db_query ($sql);
         while ($category_array = db_fetch_array($result)) {
             $sql2="SELECT count(*) FROM snippet WHERE category=".$category_array["category_id"];
             $result2 = db_query ($sql2);
             $sql3="SELECT count(*) FROM snippet_package WHERE category=".$category_array["category_id"];
             $result3 = db_query ($sql3);
             $total=(db_result($result2,0,0)+db_result($result3,0,0));
             echo '
		<LI><A HREF="/snippet/browse.php?by=cat&cat='.$category_array["category_id"].'">'.$category_array["category_name"].'</A> ('.$total.')<BR>';
         }

	?>
	</TD>
	</TR>
	</TABLE>

	<?php

}

?>
