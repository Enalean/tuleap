<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require "pre.php";

help_header("Bug Search -  Sorting results");

?>

<p>The Bug Tracking System (BTS) offers a rich set of features to help
you sort your bug selection in the most convenient way.

<h3>Basic Sort</h3>

To sort your bug report according to a given criteria simply click on the
corresponding column heading in the bug table. Clicking twice on the same
column will change the sorting direction from ascending to descending and
vice-versa. The sorting direction is shown by a small up 
(<img src="/images/up_arrow.png" border="0">) or 
down (<img src="/images/dn_arrow.png" border="0">) arrow 
 next to the sort criteria.

<h3>Mutli-column Sort</h3>

For more sophisticated processing you can also activate the multi-column sort.
 In this mode sort criteria cumulates as you click on column headings. 
So you can for instance click 'Severity' first and 'Assigned To' second to 
see who in the team is assigned critical bugs and how many.

<p>Also notice that At any point in the multi-column sort process, a
click on one of the sort criteria displayed in the list (criteria 1 >
criteria 2 > criteria 3...)  will bring you back in the sort criteria
list. Using this feature you can easily test various sorting
strategies.

<?php
help_footer();
?>
