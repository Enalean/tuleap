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
    <? util_get_content('snippet/homepage'); ?>
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
