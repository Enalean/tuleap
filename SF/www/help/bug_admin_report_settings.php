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

help_header("Bug Administration -  Defining a Report");

?>

<p>The Bug Tracking System (BTS) allows you to define personal
or project-wide bug reports. To build a new bug report you must decide
what bug fields will be used as search criteria, which ones will
appear in the report table and in which order.

The Bug report definition screen allows you to setup the following
parameters:

<ul> <li><b>Name</b>: each report must be given a name. This name must
not be too long as it will appear in a select box in the bug browsing
module when you are asked to choose what bug report you want to use to
query your bug database.

<li><b>Scope</b>: project administrators can define project-wide
reports that will be made available to all users. Non project
administrators can only define personal bug report.

<li><b>Description</b>: what is this report about...

<li><b>Field selection</b>: The bug field table shows all the bug
fields that are currently in use in your project Bug Tracking
System. For each field you can set up the following parameters:

<ul> <li><b>Use as a Search Criteria</b>: If you check this box the
field will appear as one of the selection criteria when you search the
bug database.</b>

<li><b>Rank on Search</b>: A number can be entered in this field. The
rank number allows you to place the field with respect to the
others. The fields with smaller values will appear first on the list
of selection criteria displayed on the screen. These number doesn't
have to be consecutive numbers.

<li><b>Use as a Report Column</b>: If you check this box the field
will appear as one of the column in the bug report table.

<li><b>Rank on Report</b>: A number can be entered in this field. The
rank number allows you to place the field with respect to the
others. The fields with smaller values will appear first in the bug
report table (from left to right). These number doesn't have to be
consecutive numbers.

<li><b>Column Width</b>: In case you want to impose a specific width
to the column in the report table you can specify a column width in
percentage of the total page width. This is optional and our
recommendation is to leave it blank unless your Web browser doesn't
make a good job at formatting your table.  <br>

<u>Tip</u>: if you want a column to be as narrow as possible enter a
value like 1 or 2 in the column width field.

</ul>
</ul>

<p><u>Remark</u>: it is perfectly OK to use a field as a search
criteria and not as a column in the bug report and vice versa. For the
fiels you don't want to use at all in the report leave all boxes and
text fields blank.

<?php
help_footer();
?>
