<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$
require ($DOCUMENT_ROOT.'/snippet/snippet_utils.php');

function snippet_mainpage() {
	global $SCRIPT_LANGUAGE,$SCRIPT_CATEGORY;
	?>
	<FONT face="arial, helvetica">
	<P>
	The purpose of this archive is to let you share your code snippets with the rest of Xerox. Code snippets are small pieces of code like scripts, macros, 
	and functions that does not require a full project environment to be put in place.
	<P>
	You can create a new code snippet, then post additional versions of that 
	snippet quickly and easily.
	<P>
	Once you have code snippets posted, you can also group code snippets together by creating a "Package" of snippets. 
	That package can contain multiple, specific versions of individual code snippets.
	<P>
	<H3>Browse Snippets</H3>
	<P>
	You can browse the snippet library quickly by language or category. You can also search code snippets by keywords using the Search box in the left menu pane.
	<BR>
	(Counts last updated <?php echo date("H:i:s l, F dS, Y") ?>
	<P>
	<TABLE WIDTH="100%" BORDER="0">
	<TR><TD>

	</TD></TR>

	<TR valign="top"><TD>
	<FONT face="arial, helvetica">
	<B>Browse by Language:</B>
	<P>
	<?php

// LJ What we actually want is to list the entry
// in alphabetical order
//	$count=count($SCRIPT_LANGUAGE);
//	for ($i=1; $i<$count; $i++) {

	unset($SCRIPT_LANGUAGE[0]); // We don't want 'Choose One' to appear
	asort($SCRIPT_LANGUAGE);
	while (list ($i, $val) = each($SCRIPT_LANGUAGE)) {
		$sql="SELECT count(*) FROM snippet WHERE language=$i";
		$result = db_query ($sql);

		echo '
		<LI><A HREF="/snippet/browse.php?by=lang&lang='.$i.'">'.$SCRIPT_LANGUAGE[$i].'</A> ('.db_result($result,0,0).')<BR>';
	}

	?>
	</TD>
	<TD>
	<FONT face="arial, helvetica">
	<B>Browse by Category:</B>
	<P>
	<?php

// LJ What we actually want is to list the entry
// in alphabetical order
//	$count=count($SCRIPT_CATEGORY);
//	for ($i=1; $i<$count; $i++) {


	unset($SCRIPT_CATEGORY[0]); // We don't want 'Choose One' to appear
	asort($SCRIPT_CATEGORY);
	while (list ($i, $val) = each($SCRIPT_CATEGORY)) {

		$sql="SELECT count(*) FROM snippet WHERE category=$i";
		$result = db_query ($sql);

		echo '
		<LI><A HREF="/snippet/browse.php?by=cat&cat='.$i.'">'.$SCRIPT_CATEGORY[$i].'</A> ('.db_result($result,0,0).')<BR>';
	}
	?>
	</TD>
	</TR>
	</TABLE>

	<?php

}

?>
