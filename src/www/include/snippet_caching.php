<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: snippet_caching.php 2056 2005-09-23 10:26:07Z nterray $
require_once('www/snippet/snippet_utils.php');

function snippet_mainpage() {
  global $Language;

    include($Language->getContent('snippet/homepage'));

    echo '
	<H3>'.$Language->getText('include_snippet_caching','browse_snippet').'</H3>
	<P>
	'.$Language->getText('include_snippet_caching','browse_explain').'
	<BR>
             ('.$Language->getText('include_snippet_caching','count_update',date("H:i:s l, F dS, Y")).')
	<P>
	<TABLE WIDTH="100%" BORDER="0">
	<TR><TD>

	</TD></TR>

	<TR valign="top"><TD>
	<B>'.$Language->getText('include_snippet_caching','browse_lang').':</B>
	<ul>';

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


	 echo '</ul>
	</TD>
	<TD>
	<B>'.$Language->getText('include_snippet_caching','browse_cat').':</B>
	<ul>';


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
    </ul>
	</TD>
	</TR>
	</TABLE>

	<?php

}

?>
